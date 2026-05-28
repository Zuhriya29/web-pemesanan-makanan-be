<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keranjang;
use Illuminate\Support\Facades\Auth;

class KeranjangController extends Controller
{
    public function keranjangAktif(Request $request)
    {
         
        $user = $request->user();
    
        $keranjang = Keranjang::with([
            // ✅ Batasi kolom KeranjangItem yang diambil
            'items' => fn($q) => $q->select(
                'id',
                'keranjang_id',
                'menu_id',
                'jumlah',
                'harga',
                'subtotal'
            ),
            // ✅ Batasi kolom Menu yang diambil
            'items.menu' => fn($q) => $q->select(
                'id',
                'nama_menu',
                'harga',
                'gambar',
                'status'
            ),
        ])
            ->select('id', 'user_id', 'status', 'total_harga')
            ->where('user_id', $user->id)
            ->where('status', 'aktif')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $keranjang
        ], 200);
    }

    public function tambahKeranjang(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            // ✅ cek apakah keranjang aktif sudah ada
            $keranjang = Keranjang::where('user_id', $user->id)
                ->where('status', 'aktif')
                ->first();

            if ($keranjang) {
                return response()->json([
                    'message' => 'Keranjang sudah tersedia',
                    'data' => $keranjang
                ], 200);
            }

            // ✅ buat keranjang baru
            $keranjang = Keranjang::create([
                'user_id' => $user->id,
                'status' => 'aktif'
            ]);

            return response()->json([
                'message' => 'Keranjang berhasil dibuat',
                'data' => $keranjang
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function hapusKeranjang(Request $request)
    {
        $userId = Auth::id();

        Keranjang::where('user_id', $userId)->delete();

        return response()->json([
            'message' => 'Keranjang berhasil dikosongkan'
        ], 200);
    }
}