<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Treatment::class, 'treatment');
    }

    public function index()
    {
        $treatments = Treatment::with(['patient:id,name,email', 'appointment', 'invoice', 'medicine'])->paginate(10);
        $patients = Patient::select('id', 'name', 'email')->get();
        $medicines = \App\Models\DentalMedicine::select('medicine_id', 'medicine_name', 'category', 'dosage_form', 'prescription_required')->get();
        
        $appointmentTypes = [
            'Dental Cleaning',
            'Tooth Extraction',
            'Root Canal',
            'Dental Filling',
            'Dental Crown',
            'Dental Bridge',
            'Dental Implant',
            'Teeth Whitening',
            'Orthodontic Treatment',
            'Periodontal Treatment',
            'Dental X-Ray',
            'Oral Surgery',
            'Emergency Dental Care',
            'Dental Consultation'
        ];
        
        return Inertia::render('Treatments/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'treatments' => $treatments, 
            'patients' => $patients,
            'medicines' => $medicines,
            'appointmentTypes' => $appointmentTypes
        ]);
    }

    public function show(Treatment $treatment)
    {
        $treatment->load(['patient', 'appointment', 'medicine']);
        return Inertia::render('Treatments/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'treatment' => $treatment
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'procedure' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            // Prescription fields
            'medicine_id' => 'nullable|exists:dental_medicines,medicine_id',
            'medication' => 'nullable|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'duration' => 'nullable|string',
            'prescription_amount' => 'nullable|numeric|min:0',
            'prescription_issue_date' => 'nullable|date',
            'prescription_expiry_date' => 'nullable|date',
            'prescription_instructions' => 'nullable|string',
            'max_refills' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('treatments', 'public');
        }

        // Set default prescription values
        $validated['prescription_issue_date'] = $validated['prescription_issue_date'] ?? now()->toDateString();
        $validated['prescription_status'] = 'active';
        $validated['refill_count'] = 0;

        Treatment::create($validated);

        return redirect()->route('treatments.index')->with('success', 'Treatment and prescription recorded.');
    }

    public function update(Request $request, Treatment $treatment)
    {
        // Prevent editing a treatment that already has an associated invoice
        if ($treatment->invoice()->exists()) {
            return redirect()->route('treatments.index')->with('error', 'This treatment has an invoice and cannot be edited.');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'procedure' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            // Prescription fields
            'medicine_id' => 'nullable|exists:dental_medicines,medicine_id',
            'medication' => 'nullable|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'duration' => 'nullable|string',
            'prescription_amount' => 'nullable|numeric|min:0',
            'prescription_issue_date' => 'nullable|date',
            'prescription_expiry_date' => 'nullable|date',
            'prescription_instructions' => 'nullable|string',
            'max_refills' => 'nullable|integer|min:0',
            'prescription_status' => 'nullable|in:active,completed,expired,cancelled',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($treatment->file_path) {
                Storage::disk('public')->delete($treatment->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('treatments', 'public');
        }

        $treatment->update($validated);

        return redirect()->route('treatments.index')->with('success', 'Treatment and prescription updated.');
    }

}