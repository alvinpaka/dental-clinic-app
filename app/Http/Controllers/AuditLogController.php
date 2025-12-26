<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-audit-logs');
    }

    public function index(Request $request)
    {
        $query = AuditLog::with(['user', 'clinic', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filter by clinic if user is not super-admin
        if (!auth()->user()->hasRole('super-admin')) {
            $query->where('clinic_id', auth()->user()->clinic_id);
        }

        // Filter by action
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->paginate(50);

        $actions = AuditLog::distinct()->pluck('action');

        return Inertia::render('AuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $request->only(['action', 'date_from', 'date_to']),
            'actions' => $actions,
        ]);
    }
}
