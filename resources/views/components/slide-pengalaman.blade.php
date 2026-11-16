{{-- Satu slide carousel Komunitas. Dipakai Beranda dan balasan JSON PengalamanController (slide baru).
     v2: data dibaca dari kolom (jenis, nama_tempat, rating, teks, foto_urls), bukan lagi diurai dari satu teks gabungan.
     Kiriman tanpa foto -> panel dekoratif berwarna sesuai jenis. Lebih dari satu foto -> galeri mini (komunitas.js). --}}
@props(['p', 'aktif' => false])
@php
    $jenis = $p->jenis === 'kuliner' ? 'kuliner' : 'destinasi';
    $foto = $p->foto_urls;
    $rating = max(0, min(5, (int) $p->rating));
    $ikon = $jenis === 'kuliner' ? 'fa-bowl-food' : 'fa-mountain-sun';
    $disuka = (bool) ($p->disuka_saya ?? false);
@endphp
<div @class(['carousel-item', 'active' => $aktif])>
    <article @class(['cerita-kartu', 'dom-'.$jenis])>
        <div @class(['cerita-media', 'cerita-media--kosong' => empty($foto)])>
            @if ($foto)
                <img src="{{ $foto[0] }}" alt="Foto {{ $p->nama_tempat ?: 'pengalaman' }} dari {{ $p->nama }}" class="cerita-utama" data-cerita-utama loading="lazy" decoding="async">
            @else
                <i class="fa-solid {{ $ikon }} cerita-ikon-besar" aria-hidden="true"></i>
            @endif
            <span class="cerita-jenis"><i class="fa-solid {{ $ikon }} me-1" aria-hidden="true"></i>{{ $jenis === 'kuliner' ? 'Kuliner' : 'Destinasi' }}</span>
            @if (count($foto) > 1)
                <div class="cerita-thumbs" role="group" aria-label="Pilih foto">
                    @foreach ($foto as $i => $url)
                        <button type="button" @class(['cerita-thumb', 'is-aktif' => $i === 0]) data-cerita-thumb="{{ $url }}" aria-label="Foto {{ $i + 1 }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                            <img src="{{ $url }}" alt="" loading="lazy" decoding="async">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="cerita-isi">
            <header class="cerita-kepala">
                @if ($p->nama_tempat)
                    <h3 class="cerita-tempat">{{ $p->nama_tempat }}</h3>
                @endif
                <p class="cerita-daerah"><i class="fa-solid fa-location-dot me-1" aria-hidden="true"></i>{{ $p->daerah?->nama_daerah ?: 'Sumatera Utara' }}</p>
                @if ($rating > 0)
                    <p class="cerita-bintang" role="img" aria-label="Rating {{ $rating }} dari 5">
                        @for ($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $rating ? 'is-on' : '' }}" aria-hidden="true"></i>@endfor
                    </p>
                @endif
            </header>

            <blockquote class="cerita-teks">{{ $p->teks }}</blockquote>

            <footer class="cerita-kaki">
                <div class="cerita-user">
                    <x-avatar :nama="$p->nama" :email="$p->email" :ukuran="44" kelas="cerita-avatar" />
                    <div>
                        <h4 class="cerita-user-nama">{{ $p->nama }}</h4>
                        <p class="cerita-user-waktu">{{ $p->created_at?->diffForHumans() ?? 'Baru saja' }}</p>
                    </div>
                </div>
                {{-- Suka (like). Tamu boleh melihat jumlahnya; menekan tombol mengarahkan ke halaman masuk (suka-pengalaman.js) --}}
                <button type="button" @class(['experience-like', 'is-liked' => $disuka])
                        data-suka-url="{{ route('pengalaman.suka', $p) }}" data-masuk-url="{{ route('masuk') }}"
                        aria-pressed="{{ $disuka ? 'true' : 'false' }}" aria-label="Suka pengalaman ini">
                    <i class="{{ $disuka ? 'fa-solid' : 'fa-regular' }} fa-heart" aria-hidden="true"></i>
                    <span class="experience-like-jumlah">{{ (int) ($p->suka_count ?? 0) }}</span>
                </button>
            </footer>
        </div>
    </article>
</div>
