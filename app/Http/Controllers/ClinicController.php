<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

class ClinicController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:manage-clinics')->except(['showMyClinic']);
    }

    /**
     * Show the user's own clinic.
     */
    public function showMyClinic()
    {
        $user = auth()->user();
        
        if (!$user->clinic_id) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not assigned to any clinic.');
        }

        $clinic = Clinic::with(['users' => function($query) {
            $query->with('roles');
        }])->findOrFail($user->clinic_id);

        return Inertia::render('Clinics/Show', [
            'clinic' => $clinic,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clinics = Clinic::withCount('users')->get();
        
        return Inertia::render('Clinics/Index', [
            'clinics' => $clinics,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Clinics/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Clinic fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clinics',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'is_active' => 'boolean',
            'subscription_status' => ['required', Rule::in(['trial', 'active', 'expired', 'cancelled'])],
            // Admin user fields
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        // Create the clinic
        $clinic = Clinic::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'is_active' => $validated['is_active'] ?? true,
            'subscription_status' => $validated['subscription_status'],
        ]);

        // Create the admin user
        $adminUser = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'clinic_id' => $clinic->id,
            'email_verified_at' => now(), // Auto-verify the admin email
        ]);

        // Assign admin role to the user
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminUser->assignRole($adminRole);
        }

        // Log the creation
        AuditService::logCreate($clinic);

        return redirect()->route('clinics.index')
            ->with('success', "Clinic '{$clinic->name}' and admin user '{$adminUser->name}' created successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Clinic $clinic)
    {
        $clinic->load(['users' => function($query) {
            $query->with('roles');
        }]);

        return Inertia::render('Clinics/Show', [
            'clinic' => $clinic,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clinic $clinic)
    {
        return Inertia::render('Clinics/Edit', [
            'clinic' => $clinic,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clinic $clinic)
    {
        $oldValues = $clinic->toArray();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('clinics')->ignore($clinic->id)],
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'is_active' => 'boolean',
            'subscription_status' => ['required', Rule::in(['trial', 'active', 'expired', 'cancelled'])],
        ]);

        $clinic->update($validated);
        
        // Log the update
        AuditService::logUpdate($clinic, $oldValues, $validated);

        return redirect()->route('clinics.index')
            ->with('success', 'Clinic updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clinic $clinic)
    {
        // Prevent deletion if clinic has users
        if ($clinic->users()->count() > 0) {
            return redirect()->route('clinics.index')
                ->with('error', 'Cannot delete clinic with associated users.');
        }

        // Log the deletion
        AuditService::logDelete($clinic);

        $clinic->delete();

        return redirect()->route('clinics.index')
            ->with('success', 'Clinic deleted successfully.');
    }

    /**
     * Export clinics to CSV.
     */
    public function export(Request $request)
    {
        $clinics = Clinic::withCount('users')->get();
        
        $csv = \League\Csv\Writer::createFromPath('php://temp', 'w+');
        $csv->insertOne(['ID', 'Name', 'Email', 'Phone', 'Address', 'Status', 'Subscription', 'Users Count', 'Created At']);
        
        foreach ($clinics as $clinic) {
            $csv->insertOne([
                $clinic->id,
                $clinic->name,
                $clinic->email,
                $clinic->phone,
                $clinic->address,
                $clinic->is_active ? 'Active' : 'Inactive',
                $clinic->subscription_status,
                $clinic->users_count,
                $clinic->created_at->format('Y-m-d H:i:s'),
            ]);
        }
        
        $csv->output('clinics_export.csv');
        exit;
    }
}
