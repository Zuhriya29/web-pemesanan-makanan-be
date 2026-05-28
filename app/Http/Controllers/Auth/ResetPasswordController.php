<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\User;

class ResetPasswordController extends Controller
{

    public function updatePassword(Request $request)
    {
        try {
            // Validate the request inputs
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
                'token'    => 'required',
            ], [
                'password.required'      => 'Password baru wajib diisi',
                'password.string'        => 'Password harus berupa teks',
                'password.min'           => 'Password minimal 6 karakter',
                'password.confirmed'     => 'Konfirmasi password tidak cocok',

                'token.required'         => 'Token reset password tidak valid',
            ]);

            // Find password reset record
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord || !hash_equals($resetRecord->token, hash('sha256', $request->token))) {
                return response()->json([
                    'message' => 'Token tidak valid.'
                ], 422);
            }

            // Check if the token has expired (valid for 60 minutes)
            if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                return response()->json([
                    'message' => 'Link reset password telah kedaluwarsa, silakan minta link baru.'
                ], 422);
            }

            // Find the user and update the password
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan.'
                ], 404);
            }

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Delete the used password reset record
            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json([
                'message' => 'Password berhasil diubah, silakan login.'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan server, silakan coba lagi.'
            ], 500);
        }
    }
}