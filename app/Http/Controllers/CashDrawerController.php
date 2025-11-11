<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CashDrawerController extends Controller
{
    public function active(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['active' => false]);
        }
        $active = CashSession::activeForUser($user->id);
        return response()->json(['active' => (bool) $active]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (method_exists($user, 'loadMissing')) {
            $user->loadMissing('roles');
        }
        $this->authorize('viewAny', CashSession::class, []);

        $active = CashSession::activeForUser($user->id);

        $sessionSummary = null;
        if ($active) {
            $cashIn = CashMovement::where('cash_session_id', $active->id)
                ->where('type', 'inflow')
                ->where('method', 'cash')
                ->sum('amount');
            $cashOut = CashMovement::where('cash_session_id', $active->id)
                ->where('type', 'outflow')
                ->where('method', 'cash')
                ->sum('amount');
            $expectedNow = (float) $active->opening_amount + (float) $cashIn - (float) $cashOut;
            $sessionSummary = [
                'status' => $active->status,
                'opening_amount' => (float) $active->opening_amount,
                'expected_cash_now' => (float) $expectedNow,
                'closing_amount' => $active->closing_amount !== null ? (float) $active->closing_amount : null,
                'expected_cash_at_close' => $active->expected_cash_at_close !== null ? (float) $active->expected_cash_at_close : null,
                'variance' => $active->variance !== null ? (float) $active->variance : null,
            ];
        }

        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        $movements = CashMovement::with(['payment.invoice.patient', 'refund'])
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $totals = [
            'inflow' => (float) $movements->where('type', 'inflow')->sum('amount'),
            'outflow' => (float) $movements->where('type', 'outflow')->sum('amount'),
        ];

        return Inertia::render('CashDrawer/Index', [
            'auth' => [ 'user' => $user ],
            'active_session' => $active,
            'movements' => $movements,
            'totals' => $totals,
            'session_summary' => $sessionSummary,
        ]);
    }

    public function open(Request $request)
    {
        $user = $request->user();
        $this->authorize('create', CashSession::class, []);

        $data = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (CashSession::activeForUser($user->id)) {
            return back()->withErrors(['opening_amount' => 'You already have an open cash session.']);
        }

        $session = CashSession::create([
            'opened_by' => $user->id,
            'opening_amount' => (float) $data['opening_amount'],
            'started_at' => now(),
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
        ]);

        try {
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'cash_session.open',
                'subject_type' => CashSession::class,
                'subject_id' => $session->id,
                'metadata' => [
                    'opening_amount' => (float) $data['opening_amount']
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('cash-drawer.index')->with('success', 'Cash session opened.');
    }

    public function close(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $session = CashSession::activeForUser($user->id);
        if (!$session) {
            return back()->withErrors(['closing_amount' => 'No active cash session to close.']);
        }
        $this->authorize('update', $session);

        // Compute expected cash at close (cash only)
        $cashIn = CashMovement::where('cash_session_id', $session->id)
            ->where('type', 'inflow')
            ->where('method', 'cash')
            ->sum('amount');
        $cashOut = CashMovement::where('cash_session_id', $session->id)
            ->where('type', 'outflow')
            ->where('method', 'cash')
            ->sum('amount');
        $expected = (float) $session->opening_amount + (float) $cashIn - (float) $cashOut;
        $closing = (float) $data['closing_amount'];
        $variance = $closing - $expected;

        // Require explanation when there is a discrepancy
        if (abs($variance) > 0.009 && empty($data['notes'])) {
            return back()->withErrors(['notes' => 'Please provide an explanation for the cash variance before closing the session.']);
        }

        $session->update([
            'closed_by' => $user->id,
            'closing_amount' => $closing,
            'expected_cash_at_close' => $expected,
            'variance' => $variance,
            'ended_at' => now(),
            'status' => 'closed',
            'notes' => $data['notes'] ?? $session->notes,
        ]);

        try {
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'cash_session.close',
                'subject_type' => CashSession::class,
                'subject_id' => $session->id,
                'metadata' => [
                    'closing_amount' => $closing,
                    'expected_cash_at_close' => $expected,
                    'variance' => $variance,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('cash-drawer.index')->with('success', 'Cash session closed.');
    }

    public function adjust(Request $request)
    {
        $user = $request->user();
        // Admin-only manual adjustments
        if (!method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            abort(403);
        }

        $data = $request->validate([
            'type' => 'required|in:inflow,outflow',
            'method' => 'required|in:cash,card,mobile_money,bank_transfer',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $session = CashSession::activeForUser($user->id);
        \App\Models\CashMovement::create([
            'cash_session_id' => optional($session)->id,
            'type' => $data['type'],
            'method' => $data['method'],
            'amount' => (float) $data['amount'],
            'reason' => $data['reason'],
            'created_by' => $user->id,
        ]);

        try {
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'cash_movement.adjust',
                'subject_type' => \App\Models\CashMovement::class,
                'subject_id' => null,
                'metadata' => $data,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('cash-drawer.index')->with('success', 'Manual adjustment recorded.');
    }
}
