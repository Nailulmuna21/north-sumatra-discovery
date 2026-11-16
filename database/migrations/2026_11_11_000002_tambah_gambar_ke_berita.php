<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* v2 — gambar latar kartu berita (URL, diisi admin). Kosong = gambar otomatis dari App\Support\GambarBerita. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita', function (Blueprint $table) {
            $table->string('gambar', 500)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('berita', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });
    }
};
