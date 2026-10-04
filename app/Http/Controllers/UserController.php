<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use app\Models\Campus;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function createCampusAdmin(Request $request)
    {
        if ($request->user()?->role !== 'super_admin') {
            abort(403, 'Only a Super Admin can create campus admin accounts.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'campus_id' => 'required|exists:campuses,id',
        ]);

        $existingAdmin = User::where('role', 'campus_admin')
            ->where('campus_id', $validated['campus_id'])
            ->exists();

        if ($existingAdmin) {
            return response()->json(['message' => 'A campus admin already exists for this campus.'], 400);
        }

        $admin = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'campus_admin',
            'campus_id' => $validated['campus_id'],
        ]);

        return response()->json([
            'message' => 'Campus admin account created successfully.',
            'user' => $admin
        ], 201);
    }

    public function index(Request $request)
    {
        if ($request->user()?->role !== 'super_admin') {
            abort(403, 'Only a Super Admin can view all users.');
        }

        $query = User::where('role', '!=', 'super_admin');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        $perPage = min($request->integer('per_page', 10), 50);

        $users = $query
            ->with('campus:id,name') // Eager load campus relationship
            ->paginate($perPage, ['id', 'name', 'username', 'email', 'role', 'campus_id']);

        return response()->json($users);
    }

    public function show(Request $request, $id)
    {
        if ($request->user()?->role !== 'super_admin') {
            abort(403, 'Only a Super Admin can view user details.');
        }

        $user = User::with('campus:id,name')->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()?->role !== 'super_admin') {
            abort(403, 'Only a Super Admin can update user accounts.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'password' => ['sometimes', 'required', 'confirmed', Password::min(8)],
            'role' => 'sometimes|required|in:student,campus_admin',
            'campus_id' => 'sometimes|required|exists:campuses,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User account updated successfully.',
            'user' => $user
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()?->role !== 'super_admin') {
            abort(403, 'Only a super admin can remove user accounts.');
        }

        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            abort(403, 'Cannot delete another super admin account.');
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
