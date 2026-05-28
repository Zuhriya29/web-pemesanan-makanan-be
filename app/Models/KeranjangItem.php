<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeranjangItem extends Model
{
    protected $fillable = [
        'keranjang_id',
        'menu_id',
        'jumlah',
        'harga',
        'subtotal'
    ];

    public function keranjang()
    {
        return $this->belongsTo(Keranjang::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}