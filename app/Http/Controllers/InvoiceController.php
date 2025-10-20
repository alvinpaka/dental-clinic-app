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

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index()
    {
        $invoices = Invoice::with(['patient', 'treatment', 'prescription'])->paginate(10);
        // Get patients who have treatments with costs or prescriptions
        $patients = Patient::where(function($query) {
            $query->whereHas('treatments', function($q) {
                $q->where('cost', '>', 0);
            })->orWhereHas('prescriptions');
        })->with([
            'treatments' => function($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost')->where('cost', '>', 0);
            },
            'prescriptions' => function($query) {
                $query->select('id', 'patient_id', 'medication', 'dosage', 'frequency', 'prescription_amount as amount');
            }
        ])->select('id', 'name', 'email')->get();
        return Inertia::render('Invoices/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'invoices' => $invoices,
            'patients' => $patients
        ]);
    }

    public function create(Request $request)
    {
        $invoices = Invoice::with(['patient', 'treatment', 'prescription'])->paginate(10);
        $patients = Patient::where(function($query) {
            $query->whereHas('treatments', function($q) {
                $q->where('cost', '>', 0);
            })->orWhereHas('prescriptions');
        })->with([
            'treatments' => function($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost', 'medication', 'prescription_amount')->where('cost', '>', 0);
            },
            'prescriptions' => function($query) {
                $query->select('id', 'patient_id', 'medication', 'dosage', 'frequency', 'prescription_amount as amount');
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

        // Calculate total amount based on treatment and prescription
        $totalAmount = 0;
        if ($validated['treatment_id']) {
            $treatment = Treatment::find($validated['treatment_id']);
            if ($treatment) {
                $totalAmount = $treatment->cost + ($treatment->prescription_amount ?: 0);
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

        $invoice->update($validated);

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
        $invoice->load(['patient', 'treatment', 'prescription']);
        return Inertia::render('Invoices/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'invoice' => $invoice
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

        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice marked as paid.');
    }
}