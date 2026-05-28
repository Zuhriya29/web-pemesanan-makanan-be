<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Menu;
use Illuminate\Http\Request;
use App\Http\Requests\TambahMenuRequest;
use App\Http\Requests\EditMenuRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MenuController extends Controller
{
    public function semuaMenuUser(Request $request)
    {
        $query = Menu::query()
            ->where('status', 'available'); // ✅ filter utama

        // 🔹 filter kategori (optional)
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $menus = $query
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menus
        ], 200);
    }

    public function semuaMenu(Request $request)
    {
        $query = Menu::query();

        // 🔹 filter berdasarkan kategori (optional)
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $menus = $query->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $menus
        ], 200);
    }

    public function tambahMenu(TambahMenuRequest $request)
    {
        try {
            $file = $request->file('gambar');

            // nama file unik
            $namaGambar =
                Str::random(32)
                . '.'
                . $file->getClientOriginalExtension();

            // path dalam bucket
            $path = 'menu/' . $namaGambar;

            // upload ke Supabase Storage
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

            // cek gagal upload
            if (!$response->successful()) {
                throw new \Exception(
                    'Upload gambar gagal: '
                        . $response->body()
                );
            }

            // simpan data ke database
            Menu::create([
                'nama_menu' => $request->nama_menu,
                'gambar' => $path,
                'harga' => $request->harga,
                'kategori' => $request->kategori,
                'status' => 'available',
            ]);

            return response()->json([
                'message' => 'Menu berhasil ditambahkan'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage() // hapus di production
            ], 500);
        }
    }

    public function detailMenu($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'message' => 'Menu tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'menu' => $menu
        ], 200);
    }

    public function editMenu(EditMenuRequest $request, $id)
    {
        try {
            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json([
                    'message' => 'Menu tidak ditemukan'
                ], 404);
            }

            $menu->nama_menu = $request->nama_menu;
            $menu->kategori = $request->kategori;
            $menu->harga = $request->harga;
            $menu->status = $request->status;

            // jika ada gambar baru
            if ($request->hasFile('gambar')) {

                $file = $request->file('gambar');

                // hapus gambar lama di Supabase
                if ($menu->gambar) {

                    Http::withHeaders([
                        'apikey' => env('SUPABASE_API_KEY'),
                        'Authorization' =>
                        'Bearer ' . env('SUPABASE_API_KEY'),
                    ])->delete(
                        env('SUPABASE_URL')
                        . '/storage/v1/object/storage/'
                            . ltrim($menu->gambar, '/')
                    );
                }

                // nama file baru
                $namaGambar =
                    Str::random(32)
                    . '.'
                    . $file->getClientOriginalExtension();

                // path di bucket
                $path = 'menu/' . $namaGambar;

                // upload gambar baru ke Supabase
                $response = Http::withHeaders([
                    'apikey' => env('SUPABASE_API_KEY'),
                    'Authorization' =>
                    'Bearer ' . env('SUPABASE_API_KEY'),
                    'Content-Type' =>
                    $file->getMimeType(),
                ])->withBody(
                    file_get_contents($file),
                    $file->getMimeType()
                )->put(
                    env('SUPABASE_URL')
                        . '/storage/v1/object/storage/'
                        . ltrim($path, '/')
                );

                // cek upload gagal
                if (!$response->successful()) {

                    throw new \Exception(
                        'Upload gambar gagal: '
                            . $response->body()
                    );
                }

                // simpan path database
                $menu->gambar = $path;
            }

            $menu->save();

            return response()->json([
                'message' => 'Menu berhasil diubah',
                'data' => $menu
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteMenu(string $id)
    {
        try {
            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json([
                    'message' => 'Menu tidak ditemukan'
                ], 404);
            }

            // hapus gambar jika ada
            if ($menu->gambar) {

                $response = Http::withHeaders([
                    'apikey' => env('SUPABASE_API_KEY'),
                    'Authorization' =>
                    'Bearer ' . env('SUPABASE_API_KEY'),
                ])->delete(
                    env('SUPABASE_URL')
                        . '/storage/v1/object/storage/'
                        . ltrim($menu->gambar, '/')
                );

                // optional: cek gagal hapus
                if (!$response->successful()) {

                    throw new \Exception(
                        'Gagal menghapus gambar: '
                            . $response->body()
                    );
                }
            }

            $menu->delete();

            return response()->json([
                'message' => 'Menu berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}