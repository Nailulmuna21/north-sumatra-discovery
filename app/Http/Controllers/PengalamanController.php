<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengalamanRequest;
use App\Models\Destinasi;
use App\Models\Kuliner;
use App\Models\Pengalaman;
use App\Models\PengalamanSuka;
use App\Support\FotoUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengalamanController extends Controller
{
    /** POST /pengalaman  (WAJIB LOGIN; dibatasi throttle di routes/web.php) */
    public function store(PengalamanRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Foto (0..4): sudah diperkecil browser; bila belum, diperkecil server (maks 1600 px, WebP) lalu disimpan di
        // public/storage/pengalaman. Bila ada yang gagal, foto yang SUDAH tersimpan dihapus lagi (tidak ada file yatim).
        $paths = [];
        try {
            foreach ($request->file('foto', []) as $file) {
                $paths[] = FotoUpload::simpan($file, 'pengalaman', 'public');
            }

            $pengalaman = $this->simpanCerita($request, $data, $paths);
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }
            report($e);   // detail teknis hanya tercatat di storage/logs (tidak dikirim ke browser)

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Server gagal menyimpan cerita atau fotonya. Silakan coba lagi beberapa saat lagi.'], 500);
            }
            throw $e;
        }

        // JS (fetch) meminta JSON untuk menambah slide baru; form biasa dikembalikan ke halaman
        if ($request->expectsJson()) {
            return response()->json([
                'id' => $pengalaman->id,
                'nama' => $pengalaman->nama,
                'teks' => $pengalaman->teks,
                'jenis' => $pengalaman->jenis,
                'nama_tempat' => $pengalaman->nama_tempat,
                'rating' => $pengalaman->rating,
                'foto_urls' => $pengalaman->foto_urls,
                'daerah' => $pengalaman->daerah?->nama_daerah,
                // Slide siap pakai untuk carousel (di-escape Blade; satu template dengan Beranda)
                'html' => Blade::render('<x-slide-pengalaman :p="$p" :aktif="true" />', ['p' => $pengalaman]),
            ], 201);
        }

        return back()->with('sukses', 'Terima kasih! Pengalamanmu sudah dibagikan.');
    }

    /** Cerita + baris foto dalam satu transaksi. @param list<string> $paths */
    private function simpanCerita(PengalamanRequest $request, array $data, array $paths): Pengalaman
    {
        return DB::transaction(function () use ($request, $data, $paths) {
            $p = Pengalaman::create([
                'nama' => $request->user()->name,   // nama pengunggah = nama akun
                'email' => $request->user()->email,
                'jenis' => $data['jenis'],
                'nama_tempat' => trim($data['nama_tempat']),
                'rating' => (int) $data['rating'],
                'teks' => trim($data['cerita']),    // `teks` kini berisi cerita saja
                'foto' => $paths[0] ?? null,        // sampul = foto pertama (kompatibel dengan admin & data lama)
                'daerah_id' => $data['daerah_id'],
            ]);

            foreach ($paths as $urutan => $path) {
                $p->fotos()->create(['path' => $path, 'urutan' => $urutan]);
            }

            return $p;
        })->load('daerah', 'fotos');
    }

    /**
     * GET /pengalaman/saran?jenis=kuliner|destinasi&q=so  -> saran nama untuk kolom "nama tempat" (maks 8).
     * Hanya SARAN: pengguna tetap boleh mengetik nama apa pun.
     */
    public function saran(Request $request): JsonResponse
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 60));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $model = $request->query('jenis') === 'kuliner' ? Kuliner::class : Destinasi::class;
        $pola = '%'.addcslashes($q, '%_\\').'%';

        return response()->json(
            $model::where('nama', 'like', $pola)->orderBy('nama')->limit(8)->pluck('nama')
        );
    }

    /** POST /pengalaman/{pengalaman}/suka  (wajib login) -> suka bila belum, batal suka bila sudah */
    public function suka(Request $request, Pengalaman $pengalaman): JsonResponse|RedirectResponse
    {
        $kunci = ['user_id' => $request->user()->id, 'pengalaman_id' => $pengalaman->id];

        $suka = PengalamanSuka::where($kunci)->first();

        if ($suka) {
            $suka->delete();
            $disuka = false;
        } else {
            PengalamanSuka::create($kunci);
            $disuka = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'suka' => $disuka,
                'jumlah' => $pengalaman->suka()->count(),
            ]);
        }

        return back();
    }
}
