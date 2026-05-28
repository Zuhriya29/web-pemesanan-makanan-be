<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/api/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

    // 1. Cari user berdasarkan ID
    $user = User::find($id);

    if (!$user) {
        return redirect('https://griya-dhahar-suroboyo.vercel.app/login?verified=false&reason=user_not_found');
    }

    // 2. Validasi hash
    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return redirect('https://griya-dhahar-suroboyo.vercel.app/login?verified=false&reason=invalid_hash');
    }

    // 3. Validasi signature (expired atau tidak)
    if (!$request->hasValidSignature()) {
        return redirect('https://griya-dhahar-suroboyo.vercel.app/login?verified=false&reason=expired');
    }

    // 4. Cek apakah sudah diverifikasi
    if ($user->hasVerifiedEmail()) {
        return redirect('https://griya-dhahar-suroboyo.vercel.app/login?verified=already');
    }

    // 5. Tandai sebagai terverifikasi
    $user->email_verified_at = Carbon::now();
    $user->save();

    return redirect('https://griya-dhahar-suroboyo.vercel.app/login?verified=true');

})->middleware('signed')->name('verification.verify');

// ✅ Route login wajib ada karena Laravel butuh ini internally
Route::get('/login', function () {
    return redirect('https://griya-dhahar-suroboyo.vercel.app/login');
})->name('login');

?>