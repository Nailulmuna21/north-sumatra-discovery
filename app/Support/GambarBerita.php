<?php

namespace App\Support;

use App\Models\Berita;

/**
 * Gambar latar kartu berita.
 *  1) kolom `gambar` (URL yang diisi admin) dipakai bila ada
 *  2) bila kosong -> gambar bawaan situs sesuai topik (dari label/judul): kuliner, budaya, atau destinasi
 * Topik juga dipakai CSS untuk warna aksen kartu (.dom-kuliner / .dom-budaya / .dom-destinasi).
 */
class GambarBerita
{
    public const BAWAAN = [
        'kuliner' => 'assets/img/latar-kuliner-desktop.webp',
        'budaya' => 'assets/img/latar-budaya-desktop.webp',
        'destinasi' => 'assets/img/latar-destinasi-desktop.webp',
    ];

    /** @return 'kuliner'|'budaya'|'destinasi' */
    public static function topik(Berita $b): string
    {
        $teks = mb_strtolower(($b->label ?? '').' '.$b->judul);

        return match (true) {
            (bool) preg_match('/kuliner|makan|masak|resep|arsik|soto|mie|kopi|jajan|hidangan/u', $teks) => 'kuliner',
            (bool) preg_match('/budaya|tradisi|adat|ulos|tor-tor|gondang|rumah bolon|tarian|seni/u', $teks) => 'budaya',
            default => 'destinasi',
        };
    }

    public static function url(Berita $b): string
    {
        $gambar = trim((string) $b->gambar);
        if ($gambar !== '' && preg_match('#^https?://#i', $gambar)) {
            return $gambar;
        }

        return asset(self::BAWAAN[self::topik($b)]);
    }
}
