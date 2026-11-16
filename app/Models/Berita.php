<?php

namespace App\Models;

use App\Support\GambarBerita;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Berita extends Model
{
    protected $table = 'berita';

    protected $fillable = ['judul', 'ringkasan', 'label', 'gambar', 'tanggal'];

    protected function casts(): array
    {
        return [
            // jadi objek Carbon: $berita->tanggal->translatedFormat('d F Y')
            'tanggal' => 'date',
        ];
    }

    /** $berita->gambar_url : URL dari admin, atau gambar bawaan sesuai topik */
    protected function gambarUrl(): Attribute
    {
        return Attribute::get(fn () => GambarBerita::url($this));
    }

    /** $berita->topik : kuliner | budaya | destinasi (untuk warna aksen kartu) */
    protected function topik(): Attribute
    {
        return Attribute::get(fn () => GambarBerita::topik($this));
    }
}
