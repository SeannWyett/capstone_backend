<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Validation\Rules\Password;


class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request) 
    {
        $request->validate(['email' => 'required|email']);

        $status = PasswordFacade::sendResetLink(
            $request->only('email')
        );

        if ($status !== PasswordFacade::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 400);
        }

        return response()->json(['message' => 'Password reset link sent to your email.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = PasswordFacade::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->save();

                event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );

        if ($status !== PasswordFacade::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 400);
        }

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

}
