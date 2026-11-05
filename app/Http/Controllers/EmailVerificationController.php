<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Check if hash matches
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Link verifikasi tidak valid.');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            // Auto login if not logged in
            if (!Auth::check()) {
                Auth::login($user);
            }
            return redirect()->route('verification.success');
        }

        // Mark as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Auto login the user
        if (!Auth::check()) {
            Auth::login($user);
        }

        return redirect()->route('verification.success');
    }
}
