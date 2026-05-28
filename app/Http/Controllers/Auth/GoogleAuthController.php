<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (empty($googleUser->getEmail())) {
                return redirect(env('FRONTEND_URL') . '/login?error=no_email');
            }

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'provider'  => 'google',
                    'avatar'    => $googleUser->getAvatar(),
                    'password'  => bcrypt(Str::random(24)),
                    'role'      => 'user',
                    'status'    => 'Active',
                    'join_date' => now(),
                    'email_verified_at' => now(),
                ]);
            } else {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'provider'  => 'google',
                        'email_verified_at' => now(),
                    ]);
                }
            }

            $user->update(['last_login' => now()]);

            Auth::login($user);

            // Buat Sanctum token untuk React
            $token = $user->createToken('app-token')->plainTextToken;

            // Redirect ke React dengan token
            return redirect(env('FRONTEND_URL') . '/auth/callback?token=' . $token);
        } catch (\Exception $e) {
            // Redirect ke React dengan pesan error
            return redirect(env('FRONTEND_URL') . '/login?error=' . urlencode($e->getMessage()));
        }
    }
}