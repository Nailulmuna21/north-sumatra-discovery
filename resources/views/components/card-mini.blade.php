{{-- v2: prop `favorit` (opsional) = ['url' => route('favorit.toggle', $kuliner), 'aktif' => bool].
     Bila diisi, tag kategori di atas foto diganti tombol hati (simpan ke favorit) dan nama kategori pindah ke badan kartu.
     Kartu Budaya / Destinasi tidak memakai prop ini dan tetap memakai tag kategori. --}}
{{-- Kartu kecil grid Beranda ("Kuliner Populer" & "Budaya Pilihan"). Pengganti cardMini() di components.js --}}
@props(['href', 'badge', 'tipe' => 'kuliner', 'lokasi', 'judul', 'deskripsi' => '', 'gambar' => null, 'tombolTeks' => 'Detail', 'peringkat' => null, 'favorit' => null])
@php
    // ikon badge per jenis konten / badge icon per content type
    $ikon = match ($tipe) {
        'budaya' => 'fa-masks-theater',
        'destinasi' => 'fa-mountain-sun',
        default => 'fa-bowl-food',
    };
@endphp
<div class="discovery-card-modern h-100">
    <div class="discovery-img-wrapper">
        @if ($favorit)
            @php $aktif = (bool) ($favorit['aktif'] ?? false); @endphp
            <button type="button" @class(['card-heart', 'is-aktif' => $aktif]) data-favorit-url="{{ $favorit['url'] }}" data-masuk-url="{{ route('masuk') }}"
                    aria-pressed="{{ $aktif ? 'true' : 'false' }}" aria-label="Simpan {{ $judul }} ke favorit" title="Simpan ke favorit">
                <i class="{{ $aktif ? 'fa-solid' : 'fa-regular' }} fa-heart" aria-hidden="true"></i>
            </button>
        @else
            <span @class(['card-category-tag', 'tag-budaya' => $tipe === 'budaya'])><i class="fa-solid {{ $ikon }} me-1"></i> {{ $badge }}</span>
        @endif
        <x-gambar :src="$gambar" :alt="$judul" />
        <x-lencana-peringkat :peringkat="$peringkat" class="badge-peringkat--sudut" />
    </div>
    <div class="p-4 d-flex flex-column flex-grow-1">
        <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
            <span><i class="fa-solid fa-location-dot me-1"></i> {{ $lokasi }}</span>
            @if ($favorit)
                <span class="card-kategori-mini"><i class="fa-solid {{ $ikon }} me-1" aria-hidden="true"></i>{{ $badge }}</span>
            @endif
        </div>
        <h5 class="mb-2"><a href="{{ $href }}" class="card-title-link">{{ $judul }}</a></h5>
        <p class="small text-muted mb-4 flex-grow-1">{{ $deskripsi }}</p>
        <a href="{{ $href }}" class="btn btn-sm btn-ns-outline w-100">{{ $tombolTeks }}</a>
    </div>
</div>
