<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PesananItem;
use App\Models\User;

class Pesanan extends Model
{
    protected $fillable = [
        'user_id',
        'nama_pemesan',
        'nohp',
        'catatan',
        'jenis_pemesanan',
        'total_harga',
        'bukti_pembayaran',
        'qr_token',
        'qr_code',
        'status_pesanan',
        'expired_at'
    ];

    public function items()
    {
        return $this->hasMany(PesananItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}