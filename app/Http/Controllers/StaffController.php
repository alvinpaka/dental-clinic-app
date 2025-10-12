<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::with('roles')->paginate(12);
        $roles = Role::all();

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'roles' => $roles
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
}