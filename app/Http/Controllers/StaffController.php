<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::with('roles')->paginate(10);
        $roles = Role::all();
        return Inertia::render('Staff/Index', ['staff' => $staff, 'roles' => $roles]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create($validated);
        $user->assignRole($validated['role']);

        return redirect()->route('staff.index')->with('success', 'Staff added.');
    }

    // ... update (role change), destroy
}