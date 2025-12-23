<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Clinic;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get staff based on user role
        $staffQuery = User::with('roles', 'clinic');
        
        if ($user->hasRole('super-admin')) {
            // Super admin can see all staff
            $staff = $staffQuery->paginate(12);
        } elseif ($user->clinic_id) {
            // Clinic users can only see staff from their clinic
            $staff = $staffQuery->where('clinic_id', $user->clinic_id)->paginate(12);
        } else {
            // Users without clinic assignment see no staff
            $staff = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                12,
                1,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        }
        
        // Get all roles for role assignment
        $roles = Role::all();
        
        // Get clinics for super admin
        $clinics = $user->hasRole('super-admin') ? Clinic::all() : null;
        
        // Generate simple pagination links from meta data
        $links = [];
        $currentPage = $staff->currentPage();
        $lastPage = $staff->lastPage();
        
        // Previous link
        if ($currentPage > 1) {
            $links[] = [
                'url' => request()->url() . '?page=' . ($currentPage - 1),
                'label' => 'Previous',
                'active' => false,
            ];
        } else {
            $links[] = [
                'url' => null,
                'label' => 'Previous',
                'active' => false,
            ];
        }
        
        // Page numbers
        for ($i = 1; $i <= $lastPage; $i++) {
            $links[] = [
                'url' => $i === $currentPage ? null : request()->url() . '?page=' . $i,
                'label' => (string) $i,
                'active' => $i === $currentPage,
            ];
        }
        
        // Next link
        if ($currentPage < $lastPage) {
            $links[] = [
                'url' => request()->url() . '?page=' . ($currentPage + 1),
                'label' => 'Next',
                'active' => false,
            ];
        } else {
            $links[] = [
                'url' => null,
                'label' => 'Next',
                'active' => false,
            ];
        }
        
        return Inertia::render('Staff/Index', [
            'staff' => [
                'data' => $staff->items(),
                'links' => $links,
                'meta' => [
                    'current_page' => $staff->currentPage(),
                    'last_page' => $staff->lastPage(),
                    'per_page' => $staff->perPage(),
                    'total' => $staff->total(),
                    'from' => $staff->firstItem(),
                    'to' => $staff->lastItem(),
                ]
            ],
            'roles' => $roles,
            'clinics' => $clinics,
            'filters' => [
                'search' => $request->get('search', ''),
                'role' => $request->get('role', 'all'),
                'sort_by' => $request->get('sort_by', 'name'),
                'sort_order' => $request->get('sort_order', 'asc'),
                'per_page' => $request->get('per_page', 12),
                'page' => $request->get('page', 1),
                'total' => $staff->total(),
                'from' => $staff->firstItem(),
                'to' => $staff->lastItem(),
            ],
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
            'clinic_id' => 'nullable|exists:clinics,id',
        ]);
        
        $user = auth()->user();
        
        // Only super admins can assign clinics
        $clinicId = null;
        if ($user->hasRole('super-admin')) {
            $clinicId = $request->clinic_id;
        } else {
            $clinicId = $user->clinic_id;
        }
        
        $staff = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'clinic_id' => $clinicId,
        ]);
        
        // Assign roles
        $staff->syncRoles($request->role_ids);
        
        return redirect()->route('staff.index')
            ->with('success', 'Staff member created successfully!');
    }
}
