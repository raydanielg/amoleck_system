<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    /**
     * Lightweight session probe. Also serves as the explicit "I'm still
     * here" heartbeat — the request itself renews the session server-side.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'authenticated' => (bool) $request->user(),
        ]);
    }

    /**
     * Re-authenticate an expired (or about-to-expire) session so the user
     * can keep working on the current page. Password is always verified
     * server-side; session is regenerated on success.
     */
    public function reauth(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['nullable', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user) {
            if (! Hash::check($request->password, $user->password)) {
                AuditLogger::log('auth.reauth_failed', 'user', $user->id);
                throw ValidationException::withMessages([
                    'password' => __('The password you entered is incorrect.'),
                ]);
            }
        } else {
            $email = $request->input('email');

            if (! $email || ! Auth::attempt(['email' => $email, 'password' => $request->password])) {
                AuditLogger::log('auth.reauth_failed', 'user', null, null, ['email' => $email]);
                throw ValidationException::withMessages([
                    'password' => __('Invalid credentials. Please try again.'),
                ]);
            }

            $user = $request->user();
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            AuditLogger::log('auth.reauth_blocked_inactive', 'user', $user->id);

            throw ValidationException::withMessages([
                'password' => __('This account has been deactivated. Contact the Administrator.'),
            ]);
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        AuditLogger::log('auth.reauth_success', 'user', $user->id);

        return response()->json([
            'ok' => true,
            'csrf' => csrf_token(),
        ]);
    }
}
