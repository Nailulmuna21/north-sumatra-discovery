<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Budaya;
use App\Models\Daerah;
use App\Models\Kuliner;
use App\Models\Pengalaman;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function index(): View
    {
        return view('beranda', [
            // angka di hero dihitung dari database (dulu tulisan tetap "100+")
            'jumlahKuliner' => Kuliner::count(),
            'jumlahBudaya' => Budaya::count(),

            // v2: SEMUA kuliner & budaya dimuat (dulu hanya 4 & 3) supaya strip Discover dan chip daerah
            // memakai seluruh data. Terbaru di depan. Gambar dimuat lazy di sisi klien.
            // difavoritkan_saya = apakah pengguna yang login sudah menyimpan kuliner itu (hati di kartu)
            // CATATAN RISIKO SKALA: kuliner & budaya dimuat semuanya (withAvg/withCount). Aman untuk ±30 data;
            // bila data bertambah ratusan atau pengunjung ramai di hosting gratis, batasi (limit/paginate) atau cache hasilnya.
            'kulinerSemua' => Kuliner::with(['daerah', 'kategori'])
                ->denganRating()
                ->when(auth()->check(), fn ($q) => $q->withExists(['favorit as difavoritkan_saya' => fn ($f) => $f->where('user_id', auth()->id())]))
                ->latest()->latest('id')->get(),

            'budayaSemua' => Budaya::with(['daerah', 'kategori'])
                ->latest()->latest('id')->get(),

            // 8 terbaru: dipakai marquee berita otomatis (dulu 3 kartu statis)
            'berita' => Berita::orderByDesc('tanggal')->orderByDesc('id')->limit(8)->get(),

            // chip filter daerah (tanpa "Seluruh Wilayah Sumatera Utara") + isi dropdown form pengalaman
            'daerahChip' => Daerah::untukChip()->withCount('kuliner')->get(),
            'daftarDaerah' => Daerah::orderBy('nama_daerah')->get(),

            // jumlah suka + (bila login) apakah pengguna ini sudah menyukai; dipakai tombol suka di slide
            'pengalaman' => Pengalaman::with(['daerah', 'fotos'])
                ->withCount('suka')
                ->when(auth()->check(), fn ($q) => $q->withExists(['suka as disuka_saya' => fn ($s) => $s->where('user_id', auth()->id())]))
                ->latest()->latest('id')->limit(12)->get(),

            // angka kecil di section Komunitas
            'jumlahCerita' => Pengalaman::count(),
            'jumlahKontributor' => Pengalaman::whereNotNull('email')->distinct()->count('email'),
        ]);
    }
}
