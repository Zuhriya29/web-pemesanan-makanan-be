<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\KeranjangItemController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PesananItemController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

// Resend link verifikasi
Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email kamu sudah terverifikasi.'
        ], 400);
    }

    $request->user()->sendEmailVerificationNotification();

    return response()->json([
        'message' => 'Link verifikasi baru sudah dikirim ke email kamu!'
    ], 200); // ✅ JSON, bukan back()

})->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');

Route::get('/semua-menu', [MenuController::class, 'semuaMenu']);

Route::controller(ForgotPasswordController::class)->group(function () {
    Route::post('forget-password', 'sendResetLinkEmail')->name('password.email');
});
Route::controller(ResetPasswordController::class)->group(function () {
    Route::post('reset-password', 'updatePassword')->name('password.update');
});

Route::get('/semua-menu', [MenuController::class, 'semuaMenu']);
Route::get('/semua-menu-user', [MenuController::class, 'semuaMenuUser']);
Route::get('/detail-menu/{id}', [MenuController::class, 'detailMenu']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Wajib Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [UserController::class, 'logout']);

    // Ambil data user login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::post('/tambah-akun-admin', [UserController::class, 'tambahAkunAdmin']);
    });

    // Fitur Keranjang
    Route::get('/semua-keranjang-item', [KeranjangItemController::class, 'semuaKeranjangItem']);
    Route::post('/tambah-keranjang-item', [KeranjangItemController::class, 'tambahKeranjangItem']);
    Route::put('/update-keranjang-item/{id}', [KeranjangItemController::class, 'updateKeranjangItem']);
    Route::delete('/hapus-keranjang-item/{id}', [KeranjangItemController::class, 'hapusKeranjangItem']);

    Route::get('/keranjang', [KeranjangController::class, 'keranjangAktif']);
    Route::post('/tambah-keranjang', [KeranjangController::class, 'tambahKeranjang']);
    Route::delete('/hapus-keranjang', [KeranjangController::class, 'hapusKeranjang']);

    Route::get('/pesanan-saya', [PesananController::class, 'pesananSaya']);
    Route::get('/pesanan-saya-riwayat', [PesananController::class, 'pesananSayaRiwayat']);
    Route::post('/qr-order-form', [PesananController::class, 'qrOrderForm']);
    Route::post('/cetak-qr/{id}', [PesananController::class, 'cetakQR']);
    Route::get('/show-qr/{id}', [PesananController::class, 'showQR']);
    Route::get('/scan-qr/{token}', [PesananController::class, 'scanQR']);
    Route::get('/show-pesanan', [PesananController::class, 'showPesanan']);
    Route::put('/update-status/{id}', [PesananController::class, 'updateStatus']);
    Route::get('/detail-riwayat-pesanan/{id}', [PesananItemController::class, 'detailRiwayatPesanan']);

    // Admin & Menu Management
    Route::get('/semua-admin', [UserController::class, 'semuaAdmin']);
    Route::get('/semua-user', [UserController::class, 'semuaUser']);
    Route::delete('/delete-akun-admin/{id}', [UserController::class, 'deleteAkunAdmin']);

    Route::post('/tambah-menu', [MenuController::class, 'tambahMenu']);
    Route::put('/edit-menu/{id}', [MenuController::class, 'editMenu']);
    Route::delete('/delete-menu/{id}', [MenuController::class, 'deleteMenu']);
});

Route::put('/reset-password', [UserController::class, 'resetPassword']);