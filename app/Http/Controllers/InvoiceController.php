<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReceiptMail;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['created_at', 'amount', 'status', 'due_date', 'patient'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $invoicesQuery = Invoice::with(['patient', 'treatment.prescriptions.medicine', 'payments']);

        if ($search !== '') {
            $invoicesQuery->where(function ($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('treatment', function ($treatmentQuery) use ($search) {
                        $treatmentQuery->where('procedure', 'like', "%{$search}%");
                    })
                    ->orWhereHas('prescription', function ($prescriptionQuery) use ($search) {
                        $prescriptionQuery->where('medication', 'like', "%{$search}%");
                    })
                    ->orWhereHas('treatment.prescriptions', function ($treatmentPrescriptionQuery) use ($search) {
                        $treatmentPrescriptionQuery->where('medication', 'like', "%{$search}%");
                    });
            });
        }

        if ($status && $status !== 'all') {
            $invoicesQuery->where('status', $status);
        }

        if ($sortBy === 'patient') {
            $invoicesQuery->orderBy(
                Patient::select('name')
                    ->whereColumn('patients.id', 'invoices.patient_id')
                    ->limit(1),
                $sortOrder
            );
        } else {
            $invoicesQuery->orderBy($sortBy, $sortOrder);
        }

        $invoices = $invoicesQuery
            ->paginate($perPage)
            ->withQueryString();

        // Stats
        $paymentsToday = (float) \App\Models\Payment::whereDate('received_at', Carbon::today())->sum('amount');
        $paymentsThisMonth = (float) \App\Models\Payment::whereYear('received_at', Carbon::now()->year)
            ->whereMonth('received_at', Carbon::now()->month)
            ->sum('amount');
        $overdueCount = (int) Invoice::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', Carbon::today())
            ->count();
        $outstandingTotal = Invoice::withSum('payments', 'amount')
            ->where('status', '!=', 'paid')
            ->get()
            ->sum(function ($inv) {
                $paid = (float) ($inv->payments_sum_amount ?? 0);
                $bal = max(0, (float) $inv->amount - $paid);
                return round($bal, 2);
            });
        // Get patients who have treatments with costs or prescriptions
        $patients = Patient::where(function ($query) {
            $query->whereHas('treatments', function ($q) {
                $q->where('cost', '>', 0)
                  ->whereDoesntHave('invoice'); // Exclude treatments that already have invoices
            })->orWhereHas('prescriptions', function ($q) {
                // Include prescriptions that belong to treatments that haven't been invoiced
                $q->whereHas('treatment', function ($treatmentQuery) {
                    $treatmentQuery->where('cost', '>', 0)
                                   ->whereDoesntHave('invoice');
                });
            });
        })->with([
            'treatments' => function ($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost')
                      ->where('cost', '>', 0)
                      ->whereDoesntHave('invoice') // Exclude treatments that already have invoices
                      ->with(['prescriptions' => function ($q) {
                          $q->select('prescriptions.id', 'prescriptions.treatment_id', 'prescriptions.medicine_id', 'prescriptions.medication', 'prescriptions.dosage', 'prescriptions.frequency', 'prescriptions.duration', 'prescriptions.prescription_amount as amount')->with('medicine');
                      }]);
            },
            'prescriptions' => function ($query) {
                $query->select('prescriptions.id', 'prescriptions.treatment_id', 'prescriptions.medicine_id', 'prescriptions.medication', 'prescriptions.dosage', 'prescriptions.frequency', 'prescriptions.duration', 'prescriptions.prescription_amount as amount')
                      ->whereHas('treatment', function ($treatmentQuery) {
                          $treatmentQuery->where('cost', '>', 0)
                                         ->whereDoesntHave('invoice');
                      })
                      ->with('medicine');
            }
        ])->select('id', 'name', 'email')->get();
        return Inertia::render('Invoices/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'invoices' => $invoices,
            'patients' => $patients,
            'stats' => [
                'payments_today' => $paymentsToday,
                'payments_this_month' => $paymentsThisMonth,
                'overdue_invoices' => $overdueCount,
                'outstanding_total' => (float) $outstandingTotal,
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
                'page' => (int) $request->input('page', 1),
                'total' => $invoices->total(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $invoices = Invoice::with(['patient', 'treatment', 'prescription'])->paginate(10);
        $patients = Patient::where(function($query) {
            $query->whereHas('treatments', function($q) {
                $q->where('cost', '>', 0)
                  ->whereDoesntHave('invoice'); // Exclude treatments that already have invoices
            })->orWhereHas('prescriptions', function($q) {
                // Include prescriptions that belong to treatments that haven't been invoiced
                $q->whereHas('treatment', function ($treatmentQuery) {
                    $treatmentQuery->where('cost', '>', 0)
                                   ->whereDoesntHave('invoice');
                });
            });
        })->with([
            'treatments' => function($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost', 'medication', 'prescription_amount')
                      ->where('cost', '>', 0)
                      ->whereDoesntHave('invoice'); // Exclude treatments that already have invoices
            },
            'prescriptions' => function($query) {
                $query->select('id', 'patient_id', 'medication', 'dosage', 'frequency', 'prescription_amount as amount')
                      ->whereHas('treatment', function ($treatmentQuery) {
                          $treatmentQuery->where('cost', '>', 0)
                                         ->whereDoesntHave('invoice');
                      });
            }
        ])->select('id', 'name', 'email')->get();

        $prefill = null;
        $treatmentId = $request->query('treatment_id');
        $prescriptionId = $request->query('prescription_id');

        if ($treatmentId) {
            $treatment = Treatment::with('patient')->find($treatmentId);
            if ($treatment) {
                $prefill = [
                    'patient_id' => $treatment->patient_id,
                    'treatment_id' => $treatment->id,
                    'amount' => $treatment->cost,
                    'due_date' => Carbon::now()->toDateString(),
                ];
            }
        } elseif ($prescriptionId) {
            $prescription = Prescription::with('patient')->find($prescriptionId);
            if ($prescription) {
                $prefill = [
                    'patient_id' => $prescription->patient_id,
                    'prescription_id' => $prescription->id,
                    'amount' => $prescription->amount ?: 0, // Use prescription amount if set, otherwise 0
                    'due_date' => Carbon::now()->toDateString(),
                ];
            }
        }

        return Inertia::render('Invoices/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'invoices' => $invoices,
            'patients' => $patients,
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'treatment_id' => 'nullable|exists:treatments,id',
            'prescription_ids' => 'nullable|array',
            'prescription_ids.*' => 'exists:prescriptions,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:pending,paid,overdue', // Make status optional with default
            'due_date' => 'required|date', // Remove 'after:now' to allow past/today dates
        ]);

        // Validate that only treatment OR prescriptions are selected, not both
        if ($validated['treatment_id'] && isset($validated['prescription_ids']) && !empty($validated['prescription_ids'])) {
            return back()->withErrors(['error' => 'Please select either a treatment OR prescriptions, not both.']);
        }

        // Check if treatment is already invoiced
        if ($validated['treatment_id']) {
            $existingInvoice = Invoice::where('treatment_id', $validated['treatment_id'])->first();
            if ($existingInvoice) {
                return back()->withErrors(['error' => 'This treatment has already been invoiced.']);
            }
        }

        // Calculate total amount based on treatment and prescription
        $totalAmount = 0;
        if ($validated['treatment_id']) {
            $treatment = Treatment::with('prescriptions')->find($validated['treatment_id']);
            if ($treatment) {
                $prescriptionTotal = $treatment->prescriptions->sum('prescription_amount');
                $totalAmount = $treatment->cost + $prescriptionTotal;
            }
        } elseif (isset($validated['prescription_ids']) && !empty($validated['prescription_ids'])) {
            // Handle prescription-only invoices
            $totalAmount = $validated['amount'];
        }

        // Set the calculated amount
        $validated['amount'] = $totalAmount;

        // Check if patient already has a pending invoice
        $existingInvoice = Invoice::where('patient_id', $validated['patient_id'])
            ->where('status', 'pending')
            ->first();

        if ($existingInvoice) {
            // Combine with existing pending invoice
            $newAmount = $existingInvoice->amount + $validated['amount'];

            // Update the existing invoice with the new amount
            $existingInvoice->update([
                'amount' => $newAmount,
                'treatment_id' => $validated['treatment_id'] ?: $existingInvoice->treatment_id,
                'due_date' => $validated['due_date'], // Update due date if needed
            ]);

            // Handle prescription if provided
            if (isset($validated['prescription_ids']) && !empty($validated['prescription_ids'])) {
                $existingInvoice->prescription_id = $validated['prescription_ids'][0];
                $existingInvoice->save();
            }

            $invoice = $existingInvoice;
            $message = 'Invoice updated with additional items.';
        } else {
            // Create new invoice
            $invoice = Invoice::create([
                'patient_id' => $validated['patient_id'],
                'treatment_id' => $validated['treatment_id'],
                'amount' => $validated['amount'],
                'status' => $validated['status'] ?? 'pending',
                'due_date' => $validated['due_date'],
            ]);

            // Handle prescription if provided
            if (isset($validated['prescription_ids']) && !empty($validated['prescription_ids'])) {
                $invoice->prescription_id = $validated['prescription_ids'][0];
                $invoice->save();
            }

            $message = 'Invoice created.';
        }

        // Load relationships for PDF generation
        $invoice->load(['patient', 'treatment', 'prescription']);

        try {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                // Generate PDF
                $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
                $path = 'invoices/' . $invoice->id . '.pdf';
                Storage::disk('public')->put($path, $pdf->output());
                $invoice->update(['pdf_path' => $path]);
            } else {
                \Log::warning('DomPDF not installed; skipping PDF generation for invoice ' . $invoice->id);
            }
        } catch (\Throwable $e) {
            // Log the error but continue
            \Log::error('PDF generation failed for invoice ' . $invoice->id . ': ' . $e->getMessage());
        }

        return redirect()->route('invoices.index')->with('success', $message);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'treatment_id' => 'nullable|exists:treatments,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,overdue',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Check if treatment is already invoiced (but allow updating the same invoice's treatment)
        if ($validated['treatment_id'] && $validated['treatment_id'] !== $invoice->treatment_id) {
            $existingInvoice = Invoice::where('treatment_id', $validated['treatment_id'])
                                    ->where('id', '!=', $invoice->id)
                                    ->first();
            if ($existingInvoice) {
                return back()->withErrors(['error' => 'This treatment has already been invoiced in another invoice.']);
            }
        }

        // Reload relationships for PDF regeneration if needed
        $invoice->load(['patient', 'treatment', 'prescription']);

        try {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) && $invoice->pdf_path) {
                // Regenerate PDF
                $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
                Storage::disk('public')->put($invoice->pdf_path, $pdf->output());
            }
        } catch (\Throwable $e) {
            \Log::error('PDF regeneration failed for invoice ' . $invoice->id . ': ' . $e->getMessage());
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'treatment.prescriptions.medicine', 'prescription', 'payments']);
        $refunds = \App\Models\PaymentRefund::where('invoice_id', $invoice->id)
            ->with(['refundedBy'])
            ->orderByDesc('refunded_at')
            ->get();
        return Inertia::render('Invoices/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'invoice' => $invoice,
            'refunds' => $refunds,
        ]);
    }

    public function download(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if ($invoice->pdf_path) {
            return response()->file(storage_path('app/public/' . $invoice->pdf_path));
        }
        abort(404);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function markPaid(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Admin only: restrict marking invoices as paid
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Only admins can mark invoices as paid.');
        }

        // Create a catch-up payment for any remaining balance
        $paidTotal = (float) $invoice->payments()->sum('amount');
        $remaining = max(0, (float) $invoice->amount - $paidTotal);
        if ($remaining > 0) {
            $invoice->payments()->create([
                'amount' => $remaining,
                'method' => 'manual_mark_paid',
                'received_at' => Carbon::now(),
                'reference' => 'Marked as paid',
                'notes' => 'Auto-created to reconcile balance on mark as paid',
                'received_by' => optional(request()->user())->id,
            ]);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        // Audit log
        try {
            \App\Models\AuditLog::create([
                'user_id' => optional(request()->user())->id,
                'action' => 'invoice.mark_paid',
                'subject_type' => Invoice::class,
                'subject_id' => $invoice->id,
                'metadata' => [
                    'remaining' => $remaining,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => (string) request()->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('invoices.index')->with('success', 'Invoice marked as paid.');
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|in:cash,card,mobile_money,bank_transfer',
            'received_at' => 'nullable|date',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        // Require active cash session for cash payments
        if (($validated['method'] ?? null) === 'cash') {
            $userId = optional($request->user())->id;
            $session = $userId ? \App\Models\CashSession::activeForUser((int) $userId) : null;
            if (!$session) {
                return back()->withErrors(['method' => 'You must open a cash drawer session before recording a cash payment.']);
            }
        }

        // Prevent overpayment: cap to remaining balance
        $paidTotalBefore = (float) $invoice->payments()->sum('amount');
        $remaining = max(0, (float) $invoice->amount - $paidTotalBefore);
        if ($remaining <= 0) {
            return back()->withErrors(['amount' => 'This invoice is already fully paid.']);
        }
        $amountToApply = min($remaining, (float) $validated['amount']);

        $payment = $invoice->payments()->create([
            'amount' => $amountToApply,
            'method' => $validated['method'] ?? null,
            'received_at' => $validated['received_at'] ?? now(),
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => optional($request->user())->id,
        ]);

        // Audit log for payment
        try {
            \App\Models\AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => 'payment.create',
                'subject_type' => \App\Models\Payment::class,
                'subject_id' => $payment->id,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        // Log cash movement into active session (if any)
        try {
            $userId = optional($request->user())->id;
            if ($userId) {
                $session = \App\Models\CashSession::activeForUser((int) $userId);
                \App\Models\CashMovement::create([
                    'cash_session_id' => optional($session)->id,
                    'type' => 'inflow',
                    'method' => $payment->method,
                    'amount' => (float) $payment->amount,
                    'reason' => 'Invoice payment',
                    'payment_id' => $payment->id,
                    'created_by' => $userId,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to log cash movement for payment ID '.$payment->id.': '.$e->getMessage());
        }

        // Sync invoice status if fully paid
        $invoice->refresh();
        $paidTotal = (float) $invoice->payments()->sum('amount');
        if ($paidTotal >= (float) $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        }

        // Attempt to email receipt (log driver is fine in dev)
        try {
            $invoice->loadMissing('patient');
            if (optional($invoice->patient)->email) {
                Mail::mailer('log')
                    ->to($invoice->patient->email)
                    ->queue(new PaymentReceiptMail($invoice->fresh(['patient','treatment.prescriptions.medicine','prescription.medicine','payments']), $payment));
            }
        } catch (\Throwable $e) {
            // swallow mail errors to not block UX
        }

        $message = $amountToApply < (float) $validated['amount']
            ? 'Payment recorded (excess removed as it exceeded balance).'
            : 'Payment recorded.';
        return back()->with('success', $message);
    }

    public function paymentReceipt(Invoice $invoice, \App\Models\Payment $payment)
    {
        $this->authorize('view', $invoice);
        if ($payment->invoice_id !== $invoice->id) {
            abort(404);
        }

        $invoice->load(['patient', 'treatment.prescriptions.medicine', 'prescription.medicine', 'payments']);
        $payment->load('receivedBy');

        // Compute previous and new balance around this payment
        $paidBefore = (float) $invoice->payments
            ->where('id', '<', $payment->id)
            ->sum('amount');
        $prevBalance = max(0, (float) $invoice->amount - $paidBefore);
        $newBalance = max(0, $prevBalance - (float) $payment->amount);

        $receiptUrl = route('invoices.payments.receipt', [$invoice->id, $payment->id]);

        // Refund context for this payment
        $refundedForPayment = (float) \App\Models\PaymentRefund::where('payment_id', $payment->id)->sum('amount');
        $remainingForPayment = max(0, (float) $payment->amount - $refundedForPayment);

        return view('invoices.payment-receipt', [
            'invoice' => $invoice,
            'payment' => $payment,
            'prevBalance' => round($prevBalance, 2),
            'newBalance' => round($newBalance, 2),
            'receiptUrl' => $receiptUrl,
            'refundedForPayment' => round($refundedForPayment, 2),
            'remainingForPayment' => round($remainingForPayment, 2),
        ]);
    }

    public function exportPayments()
    {
        $this->authorize('viewAny', Invoice::class);

        $filename = 'payments-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['payment_id', 'invoice_id', 'patient', 'amount', 'method', 'received_at', 'reference', 'notes']);

            \App\Models\Payment::with(['invoice.patient'])
                ->orderByDesc('received_at')
                ->chunk(1000, function ($payments) use ($out) {
                    foreach ($payments as $p) {
                        fputcsv($out, [
                            $p->id,
                            $p->invoice_id,
                            optional(optional($p->invoice)->patient)->name,
                            number_format((float) $p->amount, 2, '.', ''),
                            $p->method,
                            optional($p->received_at)->format('Y-m-d H:i:s'),
                            $p->reference,
                            $p->notes,
                        ]);
                    }
                });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function refundPayment(Request $request, Invoice $invoice, \App\Models\Payment $payment)
    {
        // Ensure user is allowed
        $this->authorize('update', $invoice);
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Only admins can issue refunds.');
        }

        // Ensure the payment belongs to the provided invoice
        if ($payment->invoice_id !== $invoice->id) {
            abort(404);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Enforce cutoff window (30 days since payment receipt)
        if ($payment->received_at && \Carbon\Carbon::parse($payment->received_at)->lt(\Carbon\Carbon::now()->subDays(30))) {
            return back()->withErrors(['amount' => 'Refund window has expired for this payment (30 days).']);
        }

        // Prevent over-refund: cap to remaining refundable on this payment and invoice
        $refundedSoFar = (float) \App\Models\PaymentRefund::where('payment_id', $payment->id)->sum('amount');
        $maxRefundable = max(0, (float) $payment->amount - $refundedSoFar);
        $invoicePaid = (float) $invoice->payments()->sum('amount');
        $invoiceRefunded = (float) \App\Models\PaymentRefund::where('invoice_id', $invoice->id)->sum('amount');
        $invoiceRemainingRefundable = max(0, $invoicePaid - $invoiceRefunded);

        $cap = min($maxRefundable, $invoiceRemainingRefundable);
        if ((float) $data['amount'] > $cap + 0.0001) {
            return back()->withErrors(['amount' => 'Refund exceeds remaining refundable amount for this payment/invoice.']);
        }

        $refund = \App\Models\PaymentRefund::create([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => (float) $data['amount'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'refunded_at' => now(),
            'refunded_by' => optional($request->user())->id,
        ]);

        // Enforce active session for cash refunds (original payment by cash)
        if ($payment->method === 'cash') {
            $userId = optional($request->user())->id;
            $session = $userId ? \App\Models\CashSession::activeForUser((int) $userId) : null;
            if (!$session) {
                return back()->withErrors(['amount' => 'You must open a cash drawer session before issuing a cash refund.']);
            }
        }

        // Audit log for refund
        try {
            \App\Models\AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => 'refund.create',
                'subject_type' => \App\Models\PaymentRefund::class,
                'subject_id' => $refund->id,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'amount' => (float) $refund->amount,
                    'reason' => $refund->reason,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ]);
        } catch (\Throwable $e) {}

        // Log refund as cash outflow in active session (if any)
        try {
            $userId = optional($request->user())->id;
            if ($userId) {
                $session = \App\Models\CashSession::activeForUser((int) $userId);
                \App\Models\CashMovement::create([
                    'cash_session_id' => optional($session)->id,
                    'type' => 'outflow',
                    'method' => $payment->method,
                    'amount' => (float) $refund->amount,
                    'reason' => 'Payment refund',
                    'refund_id' => $refund->id,
                    'created_by' => $userId,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to log cash movement for refund ID '.$refund->id.': '.$e->getMessage());
        }

        // Re-evaluate invoice status after refund
        $paid = (float) $invoice->payments()->sum('amount');
        $refunded = (float) \App\Models\PaymentRefund::where('invoice_id', $invoice->id)->sum('amount');
        $netPaid = $paid - $refunded;
        if ($netPaid <= 0) {
            $invoice->update(['status' => 'pending']);
        } elseif ($netPaid < (float) $invoice->amount) {
            // Use 'pending' for partially paid to match allowed enum statuses
            $invoice->update(['status' => 'pending']);
        } else {
            $invoice->update(['status' => 'paid']);
        }

        return back()->with('success', 'Refund recorded.');
    }
}