<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'nama_menu',
        'harga',
        'kategori',
        'gambar',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(PesananItem::class);
    }
}