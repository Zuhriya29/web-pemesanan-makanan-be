<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{

    public function register(Request $request)
    {
        // 1. Validasi
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6|confirmed'
            ],
            [
                'email.unique' => 'Email sudah terdaftar',          // opsional
                'email.email'    => 'Format email tidak valid',
                'password.confirmed' => 'Password dan konfirmasi password tidak sama',
                'password.min' => 'Password minimal 6 karakter'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Simpan user (pakai try-catch agar aman)
        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'    => "user",
            ]);

            Auth::login($user);
            $user->sendEmailVerificationNotification();
            
            $token = $user->createToken('verify-token')->plainTextToken;
            
            Auth::logout();

            // Arahkan ke halaman pemberitahuan
            return response()->json([
                'message' => 'Registrasi berhasil! Silakan cek email untuk verifikasi.',
                 'token'   => $token, 
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Pendaftaran gagal',
                'error'   => $e->getMessage() // boleh dihilangkan di production
            ], 500);
        }
    }

    public function login(Request $request)
    {
        // 1. Validasi (tambahkan 'remember' jika ingin digunakan)
        $fields = $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required',
                'remember' => 'nullable|boolean' // Tambahkan ini agar aman
            ],
            [
                'email.required' => 'Email wajib diisi',          // opsional
                'email.email'    => 'Format email tidak valid',
                'password.required' => 'Password wajib diisi'
            ]
        );

        // 2. Persiapkan Credentials (perhatikan tanda koma dan titik koma)
        $credentials = [
            'email' => $fields['email'], // Tambahkan koma di sini
            'password' => $fields['password']
        ]; // Tambahkan titik koma di sini

        // 3. Proses Attempt
        // Gunakan null coalescing (?? false) jika remember tidak dikirim
        if (!Auth::attempt($credentials, $fields['remember'] ?? false)) {
            // Cek apakah email ada di database
            $user = User::where('email', $fields['email'])->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['Email yang anda masukkan salah'],
                ]);
            } else {
                throw ValidationException::withMessages([
                    'password' => ['Password yang anda masukkan salah'],
                ]);
            }
        }

        // 4. Regenerate Session (Penting untuk keamanan)
        $user  = Auth::user();

        if (!$user->hasVerifiedEmail()) {
        Auth::logout(); // ✅ Logout dulu agar session tidak tersimpan

        return response()->json([
            'message' => 'Email belum diverifikasi. Silakan cek email kamu.',
            'verified' => false, // ✅ Frontend bisa cek flag ini
        ], 403);
    }
        $token = $user->createToken('app-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'role'    => $user->role,
            'user'    => $user,
            'token'   => $token,
        'verified' => true,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    public function semuaAdmin()
    {
        $users = User::where('role', 'admin')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    public function tambahAkunAdmin(Request $request)
    {
        // 1. Validasi
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:100',
                'email' => 'required|email',
                'password' => 'required|min:6|confirmed'
            ],
            [
                'email.required' => 'Email wajib diisi',          // opsional
                'email.email'    => 'Format email tidak valid',   // ← ini yang ditambahkan
                'email.unique'   => 'Email sudah terdaftar',
                'password.confirmed' => 'Password dan konfirmasi password tidak sama',
                'password.min'       => 'Password minimal 6 karakter'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Pendaftaran gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Simpan user dengan role admin
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'admin', 
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'Pendaftaran admin berhasil'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Pendaftaran gagal',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function deleteAkunAdmin(string $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Akun admin tidak ditemukan'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'message' => 'Akun admin berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function semuaUser()
    {
        $users = User::where('role', 'user')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }
}