<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;    



class StudentRegisterController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'required', 'string', 'max:255', 'unique:users,username',
                'regex:/^[a-zA-Z0-9_.-]+$/',
                function ($attribute, $value, $fail) {
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('The Username must not be in email format.');
                    }
                }
            ],
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

        return response()->json([
            'message' => 'Verification email resent.',
            ]);
    }

    public function verify(Request $request, $id, $hash)
    {   
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired verification link.'
                ], 403);
        }

        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link.'
                ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
                ], 400);
        }

        $user->markEmailAsVerified();
        
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully. You can now log in.']);
    }
}
