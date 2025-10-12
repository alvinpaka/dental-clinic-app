<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\Patient;
use App\Models\DentalMedicine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrescriptionController extends Controller
{
    public function index()
    {
        $prescriptions = Prescription::with(['patient', 'dentist', 'medicine'])->paginate(10);
        $patients = Patient::select('id', 'name', 'email')->get();
        $medicines = DentalMedicine::select('medicine_id', 'medicine_name', 'category', 'dosage_form', 'prescription_required')->get();
        
        return Inertia::render('Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'patients' => $patients,
            'medicines' => $medicines
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'medicine_id' => 'required|exists:dental_medicines,medicine_id',
            'dosage' => 'required|string',
            'frequency' => 'required|string',
            'duration' => 'required|string',
            'instructions' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'max_refills' => 'nullable|integer|min:0',
        ]);

        $medicine = DentalMedicine::find($validated['medicine_id']);
        
        $prescription = Prescription::create([
            'patient_id' => $validated['patient_id'],
            'dentist_id' => auth()->id(),
            'medicine_id' => $validated['medicine_id'],
            'medication' => $medicine->medicine_name,
            'dosage' => $validated['dosage'],
            'frequency' => $validated['frequency'],
            'duration' => $validated['duration'],
            'issue_date' => now()->toDateString(),
            'expiry_date' => $validated['expiry_date'],
            'instructions' => $validated['instructions'],
            'max_refills' => $validated['max_refills'] ?? 0,
            'status' => 'active',
        ]);

        return redirect()->route('prescriptions.index')->with('success', 'Prescription issued successfully.');
    }

    public function update(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'medicine_id' => 'required|exists:dental_medicines,medicine_id',
            'dosage' => 'required|string',
            'frequency' => 'required|string',
            'duration' => 'required|string',
            'instructions' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'status' => 'required|in:active,completed,expired,cancelled',
            'max_refills' => 'nullable|integer|min:0',
        ]);

        $medicine = DentalMedicine::find($validated['medicine_id']);
        
        $prescription->update([
            'patient_id' => $validated['patient_id'],
            'medicine_id' => $validated['medicine_id'],
            'medication' => $medicine->medicine_name,
            'dosage' => $validated['dosage'],
            'frequency' => $validated['frequency'],
            'duration' => $validated['duration'],
            'expiry_date' => $validated['expiry_date'],
            'instructions' => $validated['instructions'],
            'status' => $validated['status'],
            'max_refills' => $validated['max_refills'] ?? 0,
        ]);

        return redirect()->route('prescriptions.index')->with('success', 'Prescription updated successfully.');
    }

    public function show(Prescription $prescription)
    {
        $prescription->load(['patient', 'dentist', 'medicine']);
        
        return Inertia::render('Prescriptions/Show', [
            'prescription' => $prescription,
        ]);
    }

    public function edit(Prescription $prescription)
    {
        $prescription->load(['patient', 'dentist', 'medicine']);
        $patients = Patient::select('id', 'name', 'email')->get();
        $medicines = DentalMedicine::select('medicine_id', 'medicine_name', 'category', 'dosage_form', 'prescription_required')->get();
        
        return Inertia::render('Prescriptions/Edit', [
            'prescription' => $prescription,
            'patients' => $patients,
            'medicines' => $medicines,
        ]);
    }

    public function destroy(Prescription $prescription)
    {
        $prescription->delete();
        
        return redirect()->route('prescriptions.index')->with('success', 'Prescription deleted successfully.');
    }

    // ... other methods
}