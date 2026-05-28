<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KeranjangItem;
use App\Models\Keranjang;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeranjangItemController extends Controller
{
    public function semuaKeranjangItem(Request $request)
    {

        $user = $request->user();

        $keranjang = Keranjang::with([
            'items' => fn($q) => $q
                ->select('id', 'keranjang_id', 'menu_id', 'jumlah', 'harga', 'subtotal')
                ->orderBy('updated_at', 'desc'),
            'items.menu' => fn($q) => $q->select('id', 'nama_menu', 'harga', 'gambar', 'status')
        ])
            ->select('id', 'user_id', 'status', 'total_harga')
            ->where('user_id', $user->id)
            ->where('status', 'aktif')
            ->first();

        if (!$keranjang) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        return response()->json(['success' => true, 'data' => $keranjang->items], 200);
    }

   public function tambahKeranjangItem(Request $request)
{
    $start = microtime(true);
    $user = $request->user();

    $request->validate([
        'menu_id' => 'required|exists:menus,id',
        'jumlah' => 'required|integer|min:1',
    ]);

    $t1 = round((microtime(true) - $start) * 1000);
    Log::info(">> validate+exists: {$t1}ms");

    try {
        $menu = Menu::select('id', 'harga', 'status')->findOrFail($request->menu_id);
        $t2 = round((microtime(true) - $start) * 1000);
        Log::info(">> find menu: {$t2}ms");

        if ($menu->status !== 'available') {
            return response()->json(['message' => 'Menu tidak tersedia'], 422);
        }

        $keranjang = Keranjang::select('id', 'user_id', 'status', 'total_harga')
            ->firstOrCreate(
                ['user_id' => $user->id, 'status' => 'aktif'],
                ['total_harga' => 0]
            );
        $t3 = round((microtime(true) - $start) * 1000);
        Log::info(">> firstOrCreate keranjang: {$t3}ms");

        $item = KeranjangItem::where('keranjang_id', $keranjang->id)
            ->where('menu_id', $menu->id)
            ->first();
        $t4 = round((microtime(true) - $start) * 1000);
        Log::info(">> find item: {$t4}ms");

        if ($item) {
            $item->jumlah += $request->jumlah;
            $item->subtotal = $item->jumlah * $menu->harga;
            $item->save();
        } else {
            KeranjangItem::create([
                'keranjang_id' => $keranjang->id,
                'menu_id' => $menu->id,
                'jumlah' => $request->jumlah,
                'harga' => $menu->harga,
                'subtotal' => $menu->harga * $request->jumlah,
            ]);
        }
        $t5 = round((microtime(true) - $start) * 1000);
        Log::info(">> save item: {$t5}ms");

        $totalHarga = KeranjangItem::where('keranjang_id', $keranjang->id)->sum('subtotal');
        $keranjang->total_harga = $totalHarga;
        $keranjang->save();
        $t6 = round((microtime(true) - $start) * 1000);
        Log::info(">> update total: {$t6}ms");

        Log::info("TOTAL tambahKeranjangItem: {$t6}ms");

        return response()->json([
            'message' => 'Item berhasil ditambahkan ke keranjang',
            'total_harga' => $keranjang->total_harga
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan server',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function updateKeranjangItem(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // 1. Ambil item keranjang
            $item = KeranjangItem::findOrFail($id);

            // 2. Update jumlah & subtotal item
            $item->jumlah = $request->jumlah;
            $item->subtotal = $item->jumlah * $item->harga;
            $item->save();

            // 3. Hitung ulang total keranjang dari subtotal item
            $keranjang = Keranjang::with('items')
                ->findOrFail($item->keranjang_id);

            $keranjang->total_harga = $keranjang->items->sum('subtotal');
            $keranjang->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jumlah item berhasil diperbarui',
                'data' => [
                    'item' => $item,
                    'total_harga' => $keranjang->total_harga
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui jumlah item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hapusKeranjangItem($id)
    {
        $keranjangItem = KeranjangItem::find($id);

        if (!$keranjangItem) {
            return response()->json([
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        // ambil keranjang
        $keranjang = $keranjangItem->keranjang;

        // kurangi total_harga
        if ($keranjang) {
            $keranjang->total_harga -= $keranjangItem->subtotal;
            if ($keranjang->total_harga < 0) {
                $keranjang->total_harga = 0;
            }
            $keranjang->save();
        }

        // hapus item
        $keranjangItem->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus'
        ], 200);
    }
}