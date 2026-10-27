<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCredentialSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if($user?->role==='campus_admin' && $user->must_change_credentials) {
            return response()->json([
                'message' => 'You must setup your credentials first.',
                'code' => 'credentials_setup_required',
            ], 403);
        }
    
        return $next($request);
    }
}
