<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Treatment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['patient', 'treatment'])->paginate(10);
        // Get patients who have treatments with costs
        $patients = Patient::whereHas('treatments', function($query) {
            $query->where('cost', '>', 0);
        })->with([
            'treatments' => function($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost')->where('cost', '>', 0);
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
        $invoices = Invoice::with(['patient', 'treatment'])->paginate(10);
        $patients = Patient::whereHas('treatments', function($query) {
            $query->where('cost', '>', 0);
        })->with([
            'treatments' => function($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost')->where('cost', '>', 0);
            }
        ])->select('id', 'name', 'email')->get();

        $prefill = null;
        $treatmentId = $request->query('treatment_id');
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
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:pending,paid,overdue', // Make status optional with default
            'due_date' => 'required|date', // Remove 'after:now' to allow past/today dates
        ]);

        // Set default status if not provided
        $validated['status'] = $validated['status'] ?? 'pending';

        $invoice = Invoice::create($validated);

        // Load relationships for PDF generation
        $invoice->load(['patient', 'treatment']);

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

        return redirect()->route('invoices.index')->with('success', 'Invoice created.');
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
        $invoice->load(['patient', 'treatment']);

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
        $invoice->load(['patient', 'treatment']);
        return Inertia::render('Invoices/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'invoice' => $invoice
        ]);
    }

    public function download(Invoice $invoice)
    {
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
        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice marked as paid.');
    }
}