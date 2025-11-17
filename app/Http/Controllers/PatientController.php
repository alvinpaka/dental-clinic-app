<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\DentalMedicine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Patient::class, 'patient');
    }

    private function getProcedureTemplates(): array
    {
        return [
            ['name' => 'Dental Cleaning', 'cost' => 60000],
            ['name' => 'Tooth Extraction', 'cost' => 30000],
            ['name' => 'Root Canal', 'cost' => 350000],
            ['name' => 'Dental Filling', 'cost' => 90000],
            ['name' => 'Dental Crown', 'cost' => 450000],
            ['name' => 'Dental Bridge', 'cost' => 500000],
            ['name' => 'Dental Implant', 'cost' => 2500000],
            ['name' => 'Teeth Whitening', 'cost' => 300000],
            ['name' => 'Orthodontic Treatment', 'cost' => 1500000],
            ['name' => 'Periodontal Treatment', 'cost' => 200000],
            ['name' => 'Dental X-Ray', 'cost' => 50000],
            ['name' => 'Oral Surgery', 'cost' => 300000],
            ['name' => 'Emergency Dental Care', 'cost' => 150000],
            ['name' => 'Dental Consultation', 'cost' => 40000],
        ];
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $page = (int) $request->input('page', 1);
        if ($page <= 0) {
            $page = 1;
        }

        // Build query with filters and sorting
        $query = Patient::with(['appointments', 'treatments']);

        // Apply search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        if (in_array($sortBy, ['name', 'email', 'phone', 'dob', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Paginate results
        $patients = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the paginated collection
        $patients->getCollection()->transform(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'dob' => $patient->dob,
                'dob_formatted' => $patient->dob ? now()->parse($patient->dob)->format('F j, Y') : 'N/A',
                'dob_formatted_edit' => $patient->dob ? now()->parse($patient->dob)->format('Y-m-d') : '',
                'appointments' => $patient->appointments,
                'treatments' => $patient->treatments,
            ];
        });

        return Inertia::render('Patients/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'patients' => [
                'data' => $patients->items(),
                'meta' => [
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                    'from' => $patients->firstItem(),
                    'to' => $patients->lastItem(),
                    'links' => $patients->toArray()['links'] ?? [],
                ]
            ],
            'stats' => [
                'total_patients' => Patient::count(),
                'new_this_month' => Patient::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'active_appointments' => \App\Models\Appointment::where('status', 'scheduled')->count(),
            ],
            'can' => [
                // Use policy-compatible checks: 'create' is defined with only User, so class check is fine
                'createPatient' => auth()->user()?->can('create', Patient::class) ?? false,
                // 'update' and 'delete' require a Patient instance. For index-level permissions,
                // expose booleans based on roles consistent with PatientPolicy logic.
                'updatePatient' => auth()->user()?->hasAnyRole(['admin', 'receptionist']) ?? false,
                'deletePatient' => auth()->user()?->hasRole('admin') ?? false,
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

    public function show(Patient $patient, Request $request)
    {
        $patient->load(['appointments']);
        
        // Get pagination parameters with defaults
        $perPage = (int) $request->input('treatments_per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }
        
        $page = (int) $request->input('treatments_page', 1);
        if ($page <= 0) {
            $page = 1;
        }
        
        // Get paginated treatments
        $treatmentsQuery = $patient->treatments()
            ->select('id', 'patient_id', 'cost', 'created_at', 'updated_at')
            ->with([
                'procedures:id,treatment_id,name,cost', 
                'prescriptions' => function($q) {
                    $q->select('id', 'treatment_id', 'medicine_id', 'medication', 'dosage', 'frequency', 'duration', 'prescription_amount')
                      ->with('medicine:medicine_id,medicine_name');
                }
            ])
            ->latest('created_at');
            
        $treatments = $treatmentsQuery->paginate($perPage, ['*'], 'treatments_page', $page);
        
        // Get all patients for the dropdown (if needed)
        $patients = Patient::select('id', 'name', 'email', 'phone')->get();

        // Format the date of birth for display
        $patient->dob_formatted = $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('M d, Y') : null;
        
        // Unset the original treatments relation to avoid conflicts
        unset($patient->treatments);

        $procedureTemplates = $this->getProcedureTemplates();

        return Inertia::render('Patients/Show', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'patient' => $patient,
            'treatments' => [
                'data' => $treatments->items(),
                'meta' => [
                    'current_page' => $treatments->currentPage(),
                    'last_page' => $treatments->lastPage(),
                    'per_page' => $treatments->perPage(),
                    'total' => $treatments->total(),
                    'from' => $treatments->firstItem(),
                    'to' => $treatments->lastItem(),
                ],
                'links' => $treatments->toArray()['links'] ?? [],
            ],
            'patients' => $patients,
            'medicines' => DentalMedicine::select(
                'medicine_id',
                'medicine_name',
                'category',
                'dosage_form',
                'prescription_required'
            )->get(),
            'procedureTemplates' => $procedureTemplates,
            'appointmentTypes' => array_column($procedureTemplates, 'name'),
            'filters' => [
                'treatments_page' => $page,
                'treatments_per_page' => $perPage,
            ]
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