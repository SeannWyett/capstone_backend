<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;



class StudentRegisterController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:10|unique:users,username',
            'email' => [
                'required', 'email', 'unique:users,email',
                'regex:/^[\w.+-]+@sorsu\.edu\.ph$/i',
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'campus_id'  => 'required|exists:campuses,id',
        ], [
            'email.regex' => 'You must register using a valid SorSU email address.'
        ]);

        $student = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'campus_id' => $validated['campus_id'],
        ]);

        event(new \Illuminate\Auth\Events\Registered($student));

        event(new \Illuminate\Auth\Events\Registered($student));

        \Log::info('Registered event fired for user: ' . $student->id);
        \Log::info('User implements MustVerifyEmail: ' . ($student instanceof \Illuminate\Contracts\Auth\MustVerifyEmail ? 'yes' : 'no'));
        \Log::info('User email value: ' . ($student->email ?? 'NULL'));

        return response()->json([
            'message' => 'Registrations successful. Please check your email to verify your account before logging in.',
        ], 201);
    }

    public function resend(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['message' => 'No account found with that email.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email resent.']);
    }
}
