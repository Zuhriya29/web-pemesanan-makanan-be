<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('menus', function (Blueprint $table) {
        // Cek dulu apakah index sudah ada sebelum dibuat
        if (!Schema::hasIndex('menus', 'menus_status_kategori_index')) {
            $table->index(['status', 'kategori']);
        }

        if (!Schema::hasIndex('menus', 'menus_updated_at_index')) {
            $table->index('updated_at');
        }
    });
}

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex(['status', 'kategori']); // rollback composite
            $table->dropIndex(['updated_at']);          // rollback updated_at
        });
    }
};