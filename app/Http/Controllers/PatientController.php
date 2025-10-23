<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
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
            ->with(['treatments' => function($query) {
                $query->select('id', 'patient_id', 'procedure', 'cost', 'created_at')
                      ->with(['prescriptions' => function($q) {
                          $q->select('id', 'treatment_id', 'medicine_id', 'medication', 'dosage', 'frequency', 'duration', 'prescription_amount')
                            ->with('medicine:medicine_id,medicine_name');
                      }]);
            }])
            ->paginate(10);
        $patients->getCollection()->transform(function ($patient) {
            $patient->dob_formatted = $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('M d, Y') : null;
            $patient->dob_formatted_edit = $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('Y-m-d') : null;
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
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:150',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|array',
        ]);

        // Manual email uniqueness check
        if ($request->email) {
            $existingPatient = Patient::where('email', $request->email)->first();
            if ($existingPatient) {
                return redirect()->back()->withErrors(['email' => 'The email address is already in use.'])->withInput();
            }
        }

        // If age is provided but DOB is not, calculate DOB from age
        if ($validated['age'] && !$validated['dob']) {
            $validated['dob'] = now()->subYears($validated['age'])->format('Y-m-d');
        }

        // Ensure DOB is required for database
        if (!$validated['dob']) {
            return redirect()->back()->withErrors(['dob' => 'Either date of birth or age is required'])->withInput();
        }

        // Remove age from validated data before creating patient
        unset($validated['age']);

        Patient::create($validated);

        return redirect()->route('patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $patient->load(['appointments', 'treatments' => function($query) {
            $query->select('id', 'patient_id', 'procedure', 'cost', 'created_at')
                  ->with(['prescriptions' => function($q) {
                      $q->select('id', 'treatment_id', 'medicine_id', 'medication', 'dosage', 'frequency', 'duration', 'prescription_amount')
                        ->with('medicine:medicine_id,medicine_name');
                  }]);
        }, 'invoices', 'prescriptions']);
        $patients = Patient::select('id', 'name', 'email')->get();

        // Format the date of birth for display
        $patient->dob_formatted = $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('M d, Y') : null;

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
        return redirect()->route('patients.index');
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:150',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|array',
        ]);

        // Manual email uniqueness check (excluding current patient)
        if ($request->email) {
            $existingPatient = Patient::where('email', $request->email)->where('id', '!=', $patient->id)->first();
            if ($existingPatient) {
                return redirect()->back()->withErrors(['email' => 'The email address is already in use.'])->withInput();
            }
        }

        // If age is provided but DOB is not, calculate DOB from age
        if ($validated['age'] && !$validated['dob']) {
            $validated['dob'] = now()->subYears($validated['age'])->format('Y-m-d');
        }

        // Ensure DOB is provided
        if (!$validated['dob']) {
            return redirect()->back()->withErrors(['dob' => 'Either date of birth or age is required'])->withInput();
        }

        // Remove age from validated data before updating patient
        unset($validated['age']);

        $patient->update($validated);

        return redirect()->route('patients.index')->with('success', 'Patient updated.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Patient deleted.');
    }
}