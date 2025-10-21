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
        $treatments = Treatment::with(['patient:id,name,email', 'appointment', 'invoice', 'medicine', 'prescriptions.medicine'])->paginate(10);
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
        $treatment->load(['patient', 'appointment', 'medicine', 'prescriptions.medicine']);
        return Inertia::render('Treatments/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'treatment' => $treatment
        ]);
    }

    public function store(Request $request)
    {
        // Check if using new prescriptions array or old single fields
        $hasPrescriptionsArray = $request->has('prescriptions') && is_array($request->prescriptions) && !empty($request->prescriptions);
        
        // NEW: Check for medications array from frontend
        $hasMedicationsArray = $request->has('medications') && is_array($request->medications) && !empty($request->medications);

        if ($hasPrescriptionsArray) {
            // New format: multiple prescriptions
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'procedure' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
                // Prescriptions as array
                'prescriptions' => 'nullable|array',
                'prescriptions.*.medicine_id' => 'nullable|exists:dental_medicines,medicine_id',
                'prescriptions.*.medication' => 'nullable|string',
                'prescriptions.*.dosage' => 'nullable|string',
                'prescriptions.*.frequency' => 'nullable|string',
                'prescriptions.*.duration' => 'nullable|string',
                'prescriptions.*.prescription_amount' => 'nullable|numeric|min:0',
                'prescriptions.*.prescription_issue_date' => 'nullable|date',
                'prescriptions.*.prescription_expiry_date' => 'nullable|date',
                'prescriptions.*.prescription_instructions' => 'nullable|string',
                'prescriptions.*.max_refills' => 'nullable|integer|min:0',
            ]);

            $prescriptionData = $validated['prescriptions'] ?? [];
            unset($validated['prescriptions']);
        } else if ($hasMedicationsArray) {
            // Handle medications array from frontend
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'procedure' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
                'medications' => 'required|array',
                'medications.*.medicine_id' => 'required|exists:dental_medicines,medicine_id',
                'medications.*.cost' => 'required|numeric|min:0',
            ]);

            $prescriptionData = [];
            foreach ($validated['medications'] as $med) {
                $prescriptionData[] = [
                    'medicine_id' => $med['medicine_id'],
                    'prescription_amount' => $med['cost'],
                    'prescription_issue_date' => now()->toDateString(),
                    'prescription_status' => 'active',
                ];
            }
            unset($validated['medications']);
        } else {
            // Old format: validate without prescription fields
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'procedure' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            ]);

            // Check for old prescription fields and build a single prescription
            $prescriptionFields = ['medicine_id', 'medication', 'dosage', 'frequency', 'duration', 'prescription_amount', 'prescription_issue_date', 'prescription_expiry_date', 'prescription_instructions', 'max_refills'];
            $prescriptionData = [];
            $singlePrescription = [];
            foreach ($prescriptionFields as $field) {
                if ($request->has($field)) {
                    $singlePrescription[$field] = $request->input($field);
                }
            }
            if (!empty($singlePrescription)) {
                $prescriptionData[] = $singlePrescription;
            }
        }

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('treatments', 'public');
        }

        $treatment = Treatment::create($validated);

        // Create prescriptions
        foreach ($prescriptionData as $prescription) {
            $prescription['treatment_id'] = $treatment->id;
            $prescription['prescription_issue_date'] = $prescription['prescription_issue_date'] ?? now()->toDateString();
            $prescription['prescription_status'] = 'active';
            $prescription['refill_count'] = 0;
            \App\Models\Prescription::create($prescription);
        }

        return redirect()->route('treatments.index')->with('success', 'Treatment and prescriptions recorded.');
    }

    public function update(Request $request, Treatment $treatment)
    {
        // Prevent editing a treatment that already has an associated invoice
        if ($treatment->invoice()->exists()) {
            return redirect()->route('treatments.index')->with('error', 'This treatment has an invoice and cannot be edited.');
        }

        // Check if using new prescriptions array or old single fields
        $hasPrescriptionsArray = $request->has('prescriptions') && is_array($request->prescriptions);
        
        // NEW: Check for medications array from frontend
        $hasMedicationsArray = $request->has('medications') && is_array($request->medications);

        if ($hasPrescriptionsArray) {
            // New format: multiple prescriptions
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'procedure' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
                // Prescriptions as array
                'prescriptions' => 'nullable|array',
                'prescriptions.*.id' => 'nullable|exists:prescriptions,id',
                'prescriptions.*.medicine_id' => 'nullable|exists:dental_medicines,medicine_id',
                'prescriptions.*.medication' => 'nullable|string',
                'prescriptions.*.dosage' => 'nullable|string',
                'prescriptions.*.frequency' => 'nullable|string',
                'prescriptions.*.duration' => 'nullable|string',
                'prescriptions.*.prescription_amount' => 'nullable|numeric|min:0',
                'prescriptions.*.prescription_issue_date' => 'nullable|date',
                'prescriptions.*.prescription_expiry_date' => 'nullable|date',
                'prescriptions.*.prescription_instructions' => 'nullable|string',
                'prescriptions.*.max_refills' => 'nullable|integer|min:0',
                'prescriptions.*.prescription_status' => 'nullable|in:active,completed,expired,cancelled',
            ]);
        } else if ($hasMedicationsArray) {
            // Handle medications array from frontend
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'procedure' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
                'medications' => 'required|array',
                'medications.*.id' => 'nullable|exists:prescriptions,id',
                'medications.*.medicine_id' => 'required|exists:dental_medicines,medicine_id',
                'medications.*.cost' => 'required|numeric|min:0',
            ]);

            $prescriptionData = [];
            foreach ($validated['medications'] as $med) {
                $prescription = [
                    'medicine_id' => $med['medicine_id'],
                    'prescription_amount' => $med['cost'],
                ];
                
                if (isset($med['id'])) {
                    $prescription['id'] = $med['id'];
                }
                
                $prescriptionData[] = $prescription;
            }
            $validated['prescriptions'] = $prescriptionData;
            unset($validated['medications']);
        } else {
            // Old format: single prescription fields, convert to prescriptions array
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'procedure' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
                // Single prescription fields
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

            // Convert to prescriptions array
            $prescriptionFields = ['id', 'medicine_id', 'medication', 'dosage', 'frequency', 'duration', 'prescription_amount', 'prescription_issue_date', 'prescription_expiry_date', 'prescription_instructions', 'max_refills', 'prescription_status'];
            $prescriptionData = [];
            foreach ($prescriptionFields as $field) {
                if (isset($validated[$field])) {
                    $prescriptionData[$field] = $validated[$field];
                    unset($validated[$field]);
                }
            }
            if (!empty($prescriptionData)) {
                $validated['prescriptions'] = [$prescriptionData];
            } else {
                $validated['prescriptions'] = [];
            }
        }

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($treatment->file_path) {
                Storage::disk('public')->delete($treatment->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('treatments', 'public');
        }

        // Remove prescriptions from validated for treatment update
        $prescriptionData = $validated['prescriptions'] ?? [];
        unset($validated['prescriptions']);

        $treatment->update($validated);

        // Handle prescriptions: update existing, create new, delete removed
        $existingIds = $treatment->prescriptions()->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($prescriptionData as $prescription) {
            if (isset($prescription['id']) && in_array($prescription['id'], $existingIds)) {
                // Update existing
                $treatment->prescriptions()->where('id', $prescription['id'])->update($prescription);
                $updatedIds[] = $prescription['id'];
            } else {
                // Create new
                $prescription['treatment_id'] = $treatment->id;
                $prescription['prescription_issue_date'] = $prescription['prescription_issue_date'] ?? now()->toDateString();
                $prescription['prescription_status'] = $prescription['prescription_status'] ?? 'active';
                $prescription['refill_count'] = $prescription['refill_count'] ?? 0;
                Prescription::create($prescription);
            }
        }

        // Delete removed prescriptions
        $toDelete = array_diff($existingIds, $updatedIds);
        $treatment->prescriptions()->whereIn('id', $toDelete)->delete();

        return redirect()->route('treatments.index')->with('success', 'Treatment and prescriptions updated.');
    }

    public function createInvoice(Treatment $treatment)
    {
        // Check if invoice already exists
        if ($treatment->invoice) {
            return back()->with('error', 'Invoice already exists for this treatment');
        }

        // Ensure treatment has a patient
        if (!$treatment->patient_id) {
            return back()->with('error', 'Treatment must be associated with a patient to create an invoice');
        }

        // Calculate total amount including prescription costs
        $totalAmount = $treatment->cost;

        // Add prescription costs if they exist
        $prescriptionCosts = $treatment->prescriptions()->sum('prescription_amount');
        $totalAmount += $prescriptionCosts;

        // Create new invoice
        $invoice = \App\Models\Invoice::create([
            'treatment_id' => $treatment->id,
            'patient_id' => $treatment->patient_id,
            'amount' => $totalAmount,
            'status' => 'pending',
            'due_date' => now()->addDays(14),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created');
    }
}