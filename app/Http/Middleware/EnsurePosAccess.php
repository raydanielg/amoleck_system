<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;

class EnsurePosAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->role === User::ROLE_ADMIN) {
            return $next($request);
        }
        if ($user->role === User::ROLE_CLINIC_STAFF && Settings::bool('staff_can_pos', false)) {
            return $next($request);
        }

        abort(403);
    }
}
