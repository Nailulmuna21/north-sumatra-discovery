<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan foto unggahan user dalam bentuk RINGAN: sisi terpanjang dibatasi (default 1600 px),
 * disimpan sebagai WebP (kualitas 80), orientasi EXIF dibetulkan, metadata (mis. lokasi GPS) ikut terbuang.
 * Foto 3-5 MB dari HP -> ± 100-250 KB.
 *
 * v2: browser (komunitas.js) SUDAH memperkecil foto sebelum dikirim. Foto yang sudah ringan (<= SISI_MAKS px,
 * <= BATAS_RINGAN, tanpa metadata EXIF) langsung disimpan TANPA diproses ulang oleh GD. Ini penting di hosting
 * gratis: memproses satu foto HP 12 MP lewat GD memakai ± 130 MB memori (50 MP: ± 430 MB) dan membuat proses
 * mati sebelum cerita sempat tersimpan.
 *
 * Memakai ekstensi GD bawaan PHP (tanpa paket tambahan). Jika GD / WebP tidak tersedia, memori tidak cukup,
 * atau file gagal diproses, file asli disimpan apa adanya (unggahan user tidak pernah gagal karena ini).
 */
class FotoUpload
{
    public const SISI_MAKS = 1600;   // GANTI DI SINI: piksel sisi terpanjang
    public const KUALITAS = 80;      // GANTI DI SINI: kualitas WebP 0-100
    public const BATAS_RINGAN = 1536 * 1024;   // byte: foto <= ini (dan <= SISI_MAKS px) tidak diproses ulang

    /** @return string path relatif di disk, mis. "pengalaman/ab12cd.webp" */
    public static function simpan(UploadedFile $file, string $folder = 'pengalaman', string $disk = 'public'): string
    {
        @set_time_limit(60);   // bila hosting mengizinkan

        // Jalur cepat: foto kiriman browser sudah kecil -> simpan apa adanya (tanpa GD, tanpa risiko kehabisan memori)
        if (self::sudahRingan($file)) {
            return $file->store($folder, $disk);
        }

        $biner = self::perkecil($file);

        if ($biner === null) {
            return $file->store($folder, $disk);   // cadangan: simpan file asli
        }

        $path = trim($folder, '/').'/'.Str::random(40).'.webp';
        Storage::disk($disk)->put($path, $biner);

        return $path;
    }

    /** Sudah kecil, berformat JPEG/PNG/WebP, dan (untuk JPEG) tanpa blok EXIF yang bisa memuat lokasi GPS. */
    private static function sudahRingan(UploadedFile $file): bool
    {
        if ($file->getSize() > self::BATAS_RINGAN) {
            return false;
        }

        $info = [];
        $ukuran = @getimagesize($file->getRealPath(), $info);
        if (! $ukuran || ! in_array($ukuran[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return false;
        }
        if (max($ukuran[0], $ukuran[1]) > self::SISI_MAKS) {
            return false;
        }

        return ! ($ukuran[2] === IMAGETYPE_JPEG && isset($info['APP1']));   // APP1 = EXIF
    }

    /** Perkiraan aman: GD menyimpan 4 byte/piksel, dan butuh ± 3 salinan (decode, putar, skala). */
    private static function cukupMemori(string $path): bool
    {
        $ukuran = @getimagesize($path);
        if (! $ukuran) {
            return false;
        }

        $v = trim((string) ini_get('memory_limit'));
        if ($v === '' || $v === '-1') {
            return true;
        }
        $n = (int) $v;
        $batas = match (strtolower(substr($v, -1))) {
            'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n,
        };

        return $ukuran[0] * $ukuran[1] * 4 * 3 < ($batas - memory_get_usage(true)) * 0.8;
    }

    private static function perkecil(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }
        if (! self::cukupMemori($file->getRealPath())) {
            return null;   // terlalu besar untuk diproses GD di server ini -> simpan file asli (maks. 5 MB, sudah divalidasi)
        }

        $isi = @file_get_contents($file->getRealPath());
        $img = $isi === false ? false : @imagecreatefromstring($isi);
        if (! $img) {
            return null;
        }

        // Putar sesuai EXIF (foto HP sering "miring" di data, bukan di piksel) — hanya untuk JPEG
        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $exif = @exif_read_data($file->getRealPath());
            $sudut = match ($exif['Orientation'] ?? 1) {
                3 => 180, 6 => -90, 8 => 90, default => 0,
            };
            if ($sudut !== 0 && ($putar = imagerotate($img, $sudut, 0))) {
                $img = $putar;
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $skala = min(1, self::SISI_MAKS / max($w, $h));   // tidak pernah memperbesar
        if ($skala < 1) {
            $baru = imagescale($img, (int) round($w * $skala), (int) round($h * $skala), IMG_BICUBIC);
            if ($baru) {
                $img = $baru;
            }
        }

        imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);   // PNG transparan tetap transparan

        ob_start();
        $ok = imagewebp($img, null, self::KUALITAS);
        $hasil = ob_get_clean();

        return $ok && $hasil !== '' ? $hasil : null;
    }
}
