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
        Schema::create('keranjang-items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keranjang-id')->constrained('keranjangs')->cascadeOnDelete();
            $table->foreignId('menu-id')->constrained('menus')->cascadeOnDelete();
            $table->integer('jumlah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keranjang-items');
    }
};