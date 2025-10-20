<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Patient::class, 'patient');
    }

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
            'patients' => $patients,
            'can' => [
                'createPatient' => auth()->user()?->can('create', Patient::class) ?? false,
                'updatePatient' => auth()->user()?->can('update', new Patient()) ?? false,
                'deletePatient' => auth()->user()?->can('delete', new Patient()) ?? false,
            ],
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
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('patients', 'email')->ignore($patient->id)],
            'phone' => 'required|string|max:20',
            'dob' => 'required|date',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|array',
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