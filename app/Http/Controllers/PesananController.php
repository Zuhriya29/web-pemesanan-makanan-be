<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\Keranjang;
use App\Models\KeranjangItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PesananController extends Controller
{
    public function pesananSaya()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $pesanan = Pesanan::with('items.menu')
            ->where('user_id', $userId)
            ->whereIn('status_pesanan', ['pending', 'di-tolak', 'di-proses', 'selesai', 'qr-expired'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pesanan->transform(function ($p) {

            foreach ($p->items as $item) {
                if ($item->menu && $item->menu->gambar) {

                    // Kalau belum full URL
                    if (!str_contains($item->menu->gambar, 'http')) {

                        $item->menu->gambar =
                            env('SUPABASE_STORAGE_URL') . '/'
                            . ltrim($item->menu->gambar, '/');
                    }
                }
            }

            return $p;
        });

        return response()->json([
            'success' => true,
            'data' => $pesanan
        ], 200);
    }

    public function pesananSayaRiwayat()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $pesanan = Pesanan::with([
            'items.menu:id,nama_menu,gambar'
        ])
            ->where('user_id', $userId)
            ->where('status_pesanan', 'qr-expired')
            ->withSum('items', 'jumlah') // jumlah total item
            ->orderBy('updated_at', 'desc')
            ->get();

        $data = $pesanan->map(function ($p) {

            $firstMenu = optional($p->items->first())->menu;

            $gambar = null;

            if ($firstMenu && $firstMenu->gambar) {

                // kalau sudah full URL, pakai langsung
                if (str_contains($firstMenu->gambar, 'http')) {
                    $gambar = $firstMenu->gambar;
                } else {

                    // kalau masih path database
                    $gambar =
                        env('SUPABASE_STORAGE_URL') . '/'
                        . ltrim($firstMenu->gambar, '/');
                }
            }

            return [
                'pesanan_id' => $p->id,
                'status_pesanan' => $p->status_pesanan,
                'total_harga' => $p->total_harga,
                'total_jumlah' => $p->items_sum_jumlah ?? 0,
                'updated_at' => $p->updated_at,

                // menu pertama
                'menu_pertama' => [
                    'nama_menu' => $firstMenu?->nama_menu,
                    'gambar' => $gambar
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function qrOrderForm(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nama_pemesan'    => 'required|string|max:100',
                'jenis_pemesanan' => 'required|in:dine-in,take-away',
                'nohp' => 'required|string|max:15',
                'bukti_pembayaran'  => 'required|image|mimes:jpg,jpeg,png|max:1024',
            ],
            [
                'nama_pemesan.required'     => 'Nama pemesan wajib diisi',
                'nohp.required'     => 'Nomor HP wajib diisi',
                'nohp.max'     => 'Nomor HP tidak lebih dari 15 karakter',
                'jenis_pemesanan.required'  => 'Jenis pemesanan wajib dipilih',
                'bukti_pembayaran.required'  => 'Bukti Pembayaran wajib diunggah',

                'bukti_pembayaran.image'    => 'Bukti pembayaran harus berupa gambar',
                'bukti_pembayaran.mimes'    => 'Format gambar harus JPG atau PNG',
                'bukti_pembayaran.max'      => 'Ukuran gambar maksimal 1MB',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();

        // cek apakah user masih punya pesanan aktif
        $adaPesananAktif = Pesanan::where('user_id', $userId)
            ->whereIn('status_pesanan', [
                'pending',
                'di-proses',
                'selesai'
            ])
            ->exists();

        if ($adaPesananAktif) {
            return response()->json([
                'message' => 'Mohon selesaikan pesanan anda sebelumnya'
            ], 409);
        }

        // 1. Ambil keranjang aktif user
        $keranjang = Keranjang::where('user_id', $userId)
            ->where('status', 'aktif')
            ->first();

        if (!$keranjang) {
            return response()->json(['message' => 'Keranjang kosong'], 400);
        }

        // 2. Ambil item keranjang
        $keranjangItems = KeranjangItem::where('keranjang_id', $keranjang->id)->get();

        DB::beginTransaction();

        try {
            // 3. Simpan pesanan
            $path = null;

            if ($request->hasFile('bukti_pembayaran')) {

                $file = $request->file('bukti_pembayaran');

                // Nama file unik
                $fileName =
                    Str::random(40) . '.'
                    . $file->getClientOriginalExtension();

                // Folder dalam bucket storage
                $path = 'bukti-pembayaran/' . $fileName;

                // Upload ke Supabase Storage
                $response = Http::withHeaders([
                    'apikey' => env('SUPABASE_API_KEY'),
                    'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                    'Content-Type' => $file->getMimeType(),
                ])->withBody(
                    file_get_contents($file),
                    $file->getMimeType()
                )->put(
                    env('SUPABASE_URL')
                        . '/storage/v1/object/storage/'
                        . ltrim($path, '/')
                );
            }

            $pesanan = Pesanan::create([
                'user_id'         => $userId,
                'nama_pemesan'    => $request->nama_pemesan,
                'jenis_pemesanan' => $request->jenis_pemesanan,
                'nohp' => $request->nohp,
                'catatan' => $request->catatan,
                'bukti_pembayaran' => $path,
                'total_harga'     => $keranjang->total_harga,
                'status_pesanan'  => 'pending',
            ]);

            // 4. Pindahkan item
            foreach ($keranjangItems as $item) {
                PesananItem::create([
                    'pesanan_id'   => $pesanan->id,
                    'menu_id'      => $item->menu_id,
                    'jumlah'       => $item->jumlah,
                    'harga_satuan' => $item->harga,
                    'subtotal'     => $item->subtotal,
                ]);
            }

            // 5. Update status keranjang
            $keranjang->update(['status' => 'checkout']);

            DB::commit();

            return response()->json([
                'message'    => 'Pesanan berhasil dibuat',
                'pesanan_id' => $pesanan->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memproses pesanan',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function cetakQr(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $pesanan = Pesanan::findOrFail($id);

            if (in_array($pesanan->status_pesanan, ['qr-expired', 'di-tolak'])) {
                return response()->json([
                    'message' => 'QR tidak bisa dibuat — pesanan sudah final'
                ], 403);
            }

            $force = $request->query('force'); // ?force=1

            // ✅ hanya cek aktif jika tidak force
            if (!$force && $pesanan->qr_code && $pesanan->expired_at && now()->lt($pesanan->expired_at)) {
                return response()->json([
                    'message' => 'QR Code masih aktif',
                    'qr_path' =>
                    env('SUPABASE_URL')
                        . '/storage/v1/object/public/storage/'
                        . $pesanan->qr_code,
                    'expired_at' => $pesanan->expired_at
                ], 400);
            }

            // ✅ Hapus QR lama jika ada
            if ($pesanan->qr_code) {

                Http::withHeaders([
                    'apikey' => env('SUPABASE_API_KEY'),
                    'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                ])->delete(
                    env('SUPABASE_URL')
                        . '/storage/v1/object/storage/'
                        . $pesanan->qr_code
                );
            }

            // ✅ Buat token baru
            $token = Str::uuid()->toString();

            // ✅ URL scan endpoint
            $scanUrl = url("/scan-qr/" . $token);

            // ✅ Generate QR
            $fileName = "qr_order_{$pesanan->id}_" . time() . ".svg";
            $path = "qrcode/" . $fileName;

            $qrImage = QrCode::size(300)
                ->margin(2)
                ->generate($scanUrl);

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'image/svg+xml',
            ])->withBody(
                $qrImage,
                'image/svg+xml'
            )->put(
                env('SUPABASE_URL')
                    . '/storage/v1/object/storage/'
                    . $path
            );

            if (!$response->successful()) {
                throw new \Exception(
                    'Upload QR gagal: '
                        . $response->body()
                );
            }

            // ✅ Update pesanan
            $pesanan->update([
                'qr_token'   => $token,
                'qr_code'    => $path,
                'expired_at' => now()->addMinutes(15),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'QR Code berhasil dibuat',
                'qr_path' =>
                env('SUPABASE_URL')
                    . '/storage/v1/object/public/storage/'
                    . $path,
                'expired_at' => $pesanan->expired_at->toIso8601String(),
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal mencetak QR Code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showQr($id)
    {
        $userId = Auth::id();

        $pesanan = Pesanan::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$pesanan) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        return response()->json([
            'id' => $pesanan->id,
            'status_pesanan' => $pesanan->status_pesanan
        ]);
    }

    public function scanQr($token)
    {
        $pesanan = Pesanan::with('items.menu')
            ->where('qr_token', $token)
            ->first();

        if (!$pesanan) {
            return response()->json([
                'message' => 'QR tidak valid'
            ], 404);
        }

        if ($pesanan->status_pesanan === 'qr-expired' || now()->gt($pesanan->expired_at)) {
            return response()->json([
                'message' => 'QR kedaluwarsa dan tidak dapat digunakan'
            ], 403);
        }

        foreach ($pesanan->items as $item) {

            if ($item->menu && $item->menu->gambar) {

                if (!str_contains($item->menu->gambar, 'http')) {

                    $item->menu->gambar =
                        env('SUPABASE_STORAGE_URL')
                        . ltrim($item->menu->gambar);
                }
            }
        }

        return response()->json([
            'pesanan_id'      => $pesanan->id,
            'nama_pemesan'    => $pesanan->nama_pemesan,
            'nohp'         => $pesanan->nohp,
            'catatan'         => $pesanan->catatan,
            'jenis_pemesanan' => $pesanan->jenis_pemesanan,
            'total_harga'     => $pesanan->total_harga,
            'bukti_pembayaran' => $pesanan->bukti_pembayaran
                ? env('SUPABASE_STORAGE_URL') . '/'
                . ltrim(
                    $pesanan->bukti_pembayaran,
                    '/'
                )
                : null,
            'status_pesanan'          => $pesanan->status_pesanan,
            'items' => $pesanan->items,
        ]);
    }

    public function showPesanan()
    {
        $pesanan = Pesanan::with('items.menu', 'user')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($p) {

                // bukti pembayaran
                $p->bukti_pembayaran =
                    $p->bukti_pembayaran
                    ? env('SUPABASE_URL')
                    . '/storage/v1/object/public/storage/'
                    . ltrim($p->bukti_pembayaran, '/')
                    : null;

                // gambar menu
                foreach ($p->items as $item) {

                    if (
                        $item->menu &&
                        $item->menu->gambar &&
                        !str_contains($item->menu->gambar, 'http')
                    ) {

                        $item->menu->gambar =
                            env('SUPABASE_STORAGE_URL') . '/'
                            . ltrim($item->menu->gambar, '/');
                    }
                }
                return $p;
            });

        return response()->json($pesanan);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_pesanan' => 'required|in:pending,di-proses,selesai,di-tolak,qr-expired'
        ]);

        $pesanan = Pesanan::findOrFail($id);

        $pesanan->status_pesanan = $request->status_pesanan;
        $pesanan->save();

        return response()->json([
            'message' => 'Status pesanan berhasil diupdate',
            'status_pesanan' => $pesanan->status_pesanan
        ]);
    }
}