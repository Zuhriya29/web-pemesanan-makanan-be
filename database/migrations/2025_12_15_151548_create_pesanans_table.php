<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user-id')->constrained('users')->cascadeOnDelete();
            $table->string('nama-pemesan');
            $table->string('no-hp');
            $table->string('catatan')->nullable();
            $table->enum('jenis-pemesanan', ['dine-in', 'take-away']);
            $table->integer('total-harga');
            $table->string('bukti-pembayaran');
            $table->string('qr-code');
            $table->enum('status-pesanan', ['pending', 'di-proses', 'selsai', 'di-tolak'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};