<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::select(['id', 'name', 'email', 'phone', 'dob'])
            ->with([]) // Add relations if needed
            ->paginate(10);
        $patients->getCollection()->transform(function ($patient) {
            $patient->dob_formatted = $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('M d, Y') : null;
            return $patient;
        });
        return Inertia::render('Patients/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'patients' => $patients
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Create', [
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:patients,email',
            'phone' => 'required|string|max:20',
            'dob' => 'required|date',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|array',
        ]);

        Patient::create($validated);

        return redirect()->route('patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $patient->load(['appointments', 'treatments', 'invoices', 'prescriptions']);
        $patients = Patient::select('id', 'name', 'email')->get();
        return Inertia::render('Patients/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'patient' => $patient,
            'patients' => $patients
        ]);
    }

    // Update and destroy similar...
    public function edit(Patient $patient)
    {
        return Inertia::render('Patients/Edit', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'patient' => $patient
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            // Same as store
        ]);

        $patient->update($validated);

        return redirect()->route('patients.index')->with('success', 'Patient updated.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Patient deleted.');
    }
}