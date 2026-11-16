{{-- Navbar publik. Menu menyala sesuai route; tombol Masuk berubah jadi Keluar bila sudah login (F14). --}}
<nav class="navbar navbar-expand-lg fixed-top ns-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('beranda') }}">
            <x-logo-ns />
            <span class="brand-text">North Sumatra Discovery</span>
        </a>
        {{-- Toggle tema: versi mobile selalu terlihat di samping hamburger; versi desktop ada di dalam menu --}}
        <div class="d-flex align-items-center gap-2 ms-auto me-2 d-lg-none">
            <button type="button" class="tema-toggle js-tema-toggle " aria-label="Ganti ke tema terang" aria-pressed="true" title="Ganti tema">
                <i class="fa-solid fa-moon ikon-bulan" aria-hidden="true"></i><i class="fa-solid fa-sun ikon-matahari" aria-hidden="true"></i>
            </button>
        </div>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-lg-center gap-lg-1 mb-2 mb-lg-0">
                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('beranda')]) href="{{ route('beranda') }}" data-nav-beranda>Beranda</a></li>
                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('kuliner.*')]) href="{{ route('kuliner.index') }}">Kuliner</a></li>
                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('budaya.*')]) href="{{ route('budaya.index') }}">Budaya &amp; Tradisi</a></li>
                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('destinasi.*')]) href="{{ route('destinasi.index') }}">Tempat Singgah</a></li>
                {{-- v2: "Komunitas" = lompat ke section di Beranda (bukan halaman baru); menyala saat section terlihat (navbar-v2.js) --}}
                <li class="nav-item"><a class="nav-link" href="{{ route('beranda') }}#komunitas" data-nav-komunitas>Komunitas</a></li>
                {{-- v2: Favorit = ikon hati. Tamu yang menekan diarahkan ke halaman masuk oleh middleware auth --}}
                <li class="nav-item"><a @class(['nav-link', 'nav-heart', 'active' => request()->routeIs('favorit.*')]) href="{{ route('favorit.index') }}" aria-label="Favorit saya" title="Favorit saya"><i class="fa-solid fa-heart" aria-hidden="true"></i><span class="d-lg-none ms-2">Favorit</span></a></li>
                <li class="nav-item d-none d-lg-block ms-lg-2">
                    <button type="button" class="tema-toggle js-tema-toggle " aria-label="Ganti ke tema terang" aria-pressed="true" title="Ganti tema">
                <i class="fa-solid fa-moon ikon-bulan" aria-hidden="true"></i><i class="fa-solid fa-sun ikon-matahari" aria-hidden="true"></i>
            </button>
                </li>
                <li class="nav-item ms-lg-2">
                    @auth
                        {{-- Keluar harus POST + CSRF (bukan link biasa) --}}
                        <form method="POST" action="{{ route('keluar') }}">
                            @csrf
                            <button type="submit" class="btn btn-ns-outline btn-nav">Keluar</button>
                        </form>
                    @else
                        <a class="btn btn-ns-outline btn-nav text-decoration-none" href="{{ route('masuk') }}">
                            <i class="fa-solid fa-user me-2" aria-hidden="true"></i>Masuk
                        </a>
                    @endauth
                </li>
            </ul>
        </div>
    </div>
</nav>
