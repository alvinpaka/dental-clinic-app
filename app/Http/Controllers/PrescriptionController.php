<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrescriptionController extends Controller
{
    public function index()
    {
        $prescriptions = Prescription::with(['patient', 'dentist'])->paginate(10);
        return Inertia::render('Prescriptions/Index', ['prescriptions' => $prescriptions]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'dentist_id' => 'required|exists:users,id',
            'medication' => 'required|string',
            'dosage' => 'required|string',
            'issue_date' => 'required|date',
            'instructions' => 'nullable|string',
        ]);

        Prescription::create($validated);

        return redirect()->route('prescriptions.index')->with('success', 'Prescription issued.');
    }

    // ... other methods
}