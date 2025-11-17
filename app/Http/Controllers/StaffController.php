<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'staff');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        if ($perPage <= 0) {
            $perPage = 12;
        }

        $search = trim((string) $request->input('search', ''));
        $roleFilter = $request->input('role', 'all');
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['name', 'email', 'created_at'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $query = User::with('roles');

        // Apply search
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($roleFilter !== 'all') {
            $query->whereHas('roles', function($q) use ($roleFilter) {
                $q->where('id', $roleFilter);
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        $staff = $query->paginate($perPage)->withQueryString();
        $roles = Role::all();

        return Inertia::render('Staff/Index', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'staff' => $staff,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
                'page' => (int) $request->input('page', 1),
                'total' => $staff->total(),
                'from' => $staff->firstItem(),
                'to' => $staff->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->roles()->sync($validated['role_ids']);

        return back()->with('success', 'Staff member created successfully');
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($staff->id)],
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $staff->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $staff->roles()->sync($validated['role_ids']);

        return back()->with('success', 'Staff member updated successfully');
    }

    public function updateRoles(Request $request, User $staff)
    {
        $this->authorize('update', $staff);

        $validated = $request->validate([
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $staff->roles()->sync($validated['role_ids']);

        return back()->with('success', 'Staff roles updated successfully');
    }

    public function destroy(User $staff)
    {
        // Prevent self-deletion
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $staff->delete();

        return back()->with('success', 'Staff member deleted successfully');
    }

    public function sendResetLink(Request $request, User $staff)
    {
        $this->authorize('update', $staff);

        if (!$staff->email) {
            return back()->with('error', 'User has no email address');
        }

        $status = Password::sendResetLink(['email' => $staff->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Password reset link sent to '.$staff->email);
        }

        return back()->with('error', __($status));
    }
}