<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keranjang extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total_harga',
    ];

    public function items()
    {
        return $this->hasMany(KeranjangItem::class);
    }
}