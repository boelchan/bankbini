<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Pastikan pengguna sudah login dan akunnya berstatus aktif.
     * Akun yang dinonaktifkan administrator akan langsung dikeluarkan dari sesi.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || $request->user()->status !== 'active') {
            $wasLoggedIn = Auth::check();

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'error',
                $wasLoggedIn ? 'Akun Anda tidak aktif. Silakan hubungi administrator.' : 'Silakan masuk terlebih dahulu.'
            );
        }

        return $next($request);
    }
}
