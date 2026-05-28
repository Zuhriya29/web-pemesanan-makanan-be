<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PesananItem;
use App\Models\Pesanan;

class PesananItemController extends Controller
{
    public function detailRiwayatPesanan(Request $request, $id) 
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // Ambil pesanan sesuai ID
        $pesanan = Pesanan::where('id', $id)
            ->where('user_id', $user->id)
            ->where(
                'status_pesanan',
                'qr-expired'
            )
            ->first();

        if (!$pesanan) {
            return response()->json([
                'message' =>
                'Pesanan tidak ditemukan'
            ], 404);
        }

        // Ambil item pesanan
        $items = PesananItem::with('menu')
            ->where(
                'pesanan_id',
                $pesanan->id
            )
            ->get();

        $dataItems = $items->map(function (
            $item
        ) {

            $gambar = null;

if (
    $item->menu &&
    $item->menu->gambar
) {

    $gambar = str_contains(
        $item->menu->gambar,
        'http'
    )
        ? $item->menu->gambar
        : env('SUPABASE_STORAGE_URL') . '/'
            . ltrim(
                $item->menu->gambar,
                '/'
            );
}

            return [
                'menu_id' =>
                $item->menu?->id,

                'nama_menu' =>
                $item->menu
                    ?->nama_menu,

                'gambar' =>
                $gambar,

                'jumlah' =>
                $item->jumlah,

                'subtotal' =>
                $item->subtotal
            ];
        });

        return response()->json([
            'success' => true,

            'data' => [
                'pesanan_id' =>
                $pesanan->id,

                'status_pesanan' =>
                $pesanan
                    ->status_pesanan,

                'updated_at' =>
                $pesanan
                    ->updated_at,

                'total_harga' =>
                $pesanan
                    ->total_harga,

                'items' =>
                $dataItems
            ]
        ], 200);
    }
}