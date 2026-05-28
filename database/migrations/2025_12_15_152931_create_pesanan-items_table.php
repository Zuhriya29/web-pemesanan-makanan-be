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
        Schema::create('pesanan-items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan-id')->constrained('pesanans')->cascadeOnDelete();
            $table->foreignId('menu-id')->constrained('menus')->cascadeOnDelete();
            $table->integer('jumlah');
            $table->integer('harga-satuan');
            $table->integer('subtotal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan-items');
    }
};