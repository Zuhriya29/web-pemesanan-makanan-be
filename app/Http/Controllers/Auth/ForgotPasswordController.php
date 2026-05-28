<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
            'email.exists'   => 'Email tidak ditemukan, pastikan email sudah terdaftar.',
        ]);

        try {
            $token = Str::random(40);

            // Delete existing reset tokens
            DB::table('password_resets')->where('email', $request->email)->delete();

            // Store new reset token (plain text)
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => hash('sha256', $token),
                'created_at' => Carbon::now(),
            ]);

            $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

            // Send email
            Mail::send('auth.verify', ['resetUrl' => $resetUrl], function ($message) use ($request) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($request->email)->subject('Reset Password Notification');
            });

            return response()->json([
                'message' => 'Link reset password telah dikirim ke email kamu.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server, silakan coba lagi.',
            ], 500);
        }
    }
}