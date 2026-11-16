{{-- Composer "ala media sosial" di atas carousel Komunitas (menggantikan tombol "Bagikan ceritamu" + modal).
     WAJIB LOGIN: tamu melihat versi terkunci dengan ajakan masuk. Tanpa JS tetap jalan sebagai form multipart biasa
     ke POST /pengalaman; dengan JS: komunitas.js (pratinjau foto, progres, slide baru tanpa reload). --}}
@props(['daftarDaerah'])
@php
    $user = auth()->user();
    $galatForm = old('_form') === 'pengalaman';
    $jenisLama = old('jenis', 'destinasi');
@endphp

@guest
    <div class="composer composer--tamu" id="composerPengalaman">
        <div class="composer-baris">
            <span class="composer-avatar composer-avatar--tamu" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
            <div class="composer-tamu-teks">
                <p class="composer-tamu-judul">Punya cerita dari Sumatera Utara?</p>
                <p class="composer-tamu-sub">Masuk dulu supaya ceritamu terpercaya dan bisa dibaca pengunjung lain.</p>
            </div>
            <a href="{{ route('masuk') }}" class="btn btn-ns-primary composer-kirim"><i class="fa-solid fa-right-to-bracket me-2" aria-hidden="true"></i>Masuk atau Daftar</a>
        </div>
    </div>
@else
    <form id="formPengalaman" data-composer class="composer dom-{{ $jenisLama === 'kuliner' ? 'kuliner' : 'destinasi' }}" method="POST"
          action="{{ route('pengalaman.store') }}" enctype="multipart/form-data" novalidate
          data-saran-url="{{ route('pengalaman.saran') }}" data-maks-foto="{{ \App\Models\Pengalaman::MAKS_FOTO }}">
        @csrf
        <input type="hidden" name="_form" value="pengalaman">

        <div class="composer-baris">
            <x-avatar :nama="$user->name" :email="$user->email" :ukuran="48" kelas="composer-avatar" />

            <div class="composer-utama">
                {{-- Jenis: Kuliner / Destinasi (segmented control; warna composer ikut berganti) --}}
                <div class="composer-jenis" role="radiogroup" aria-label="Jenis pengalaman">
                    <input type="radio" name="jenis" id="jenisDestinasi" value="destinasi" @checked($jenisLama !== 'kuliner')>
                    <label for="jenisDestinasi"><i class="fa-solid fa-mountain-sun me-1" aria-hidden="true"></i>Destinasi</label>
                    <input type="radio" name="jenis" id="jenisKuliner" value="kuliner" @checked($jenisLama === 'kuliner')>
                    <label for="jenisKuliner"><i class="fa-solid fa-bowl-food me-1" aria-hidden="true"></i>Kuliner</label>
                </div>

                <label class="visually-hidden" for="composerCerita">Ceritamu</label>
                <textarea id="composerCerita" name="cerita" class="composer-cerita" rows="2" maxlength="2000" required
                          placeholder="Bagaimana pengalamanmu? Ceritakan rasanya, suasananya, atau tipsnya...">{{ old('cerita') }}</textarea>

                <div class="composer-medan">
                    <div class="composer-field">
                        <label class="visually-hidden" for="composerTempat">Nama kuliner atau destinasi</label>
                        <i class="fa-solid fa-tag" aria-hidden="true"></i>
                        <input type="text" id="composerTempat" name="nama_tempat" maxlength="150" required autocomplete="off" list="saranTempat"
                               placeholder="Nama destinasi (mis. Danau Toba)" value="{{ old('nama_tempat') }}"
                               data-ph-destinasi="Nama destinasi (mis. Danau Toba)" data-ph-kuliner="Nama makanan atau warung (mis. Soto Sinar Pagi)">
                        <datalist id="saranTempat"></datalist>
                    </div>
                    <div class="composer-field">
                        <label class="visually-hidden" for="composerDaerah">Daerah yang dikunjungi</label>
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <select id="composerDaerah" name="daerah_id" required>
                            <option value="" disabled @selected(! old('daerah_id'))>Pilih daerah...</option>
                            @foreach ($daftarDaerah as $d)
                                <option value="{{ $d->id }}" @selected((string) old('daerah_id') === (string) $d->id)>{{ $d->nama_daerah }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Pratinjau foto (diisi komunitas.js) --}}
                <div class="composer-foto-grid" id="composerPratinjau" hidden></div>
                <input type="file" id="inputFotoPengalaman" name="foto[]" accept="image/png, image/jpeg, image/webp" multiple class="d-none">

                <div id="pengalamanGalat" role="alert" @class(['composer-galat', 'd-none' => ! ($galatForm && $errors->any())])>
                    @if ($galatForm)
                        @foreach ($errors->all() as $galat)<div>{{ $galat }}</div>@endforeach
                    @endif
                </div>

                <div class="composer-bar">
                    <div class="composer-alat">
                        <button type="button" class="composer-ikon" id="composerFotoBtn" aria-label="Tambahkan foto (maks {{ \App\Models\Pengalaman::MAKS_FOTO }})" title="Tambahkan foto">
                            <i class="fa-regular fa-image" aria-hidden="true"></i>
                        </button>
                        <fieldset class="rate-pick">
                            <legend class="visually-hidden">Rating</legend>
                            <div class="rate-stars">
                                @foreach ([5, 4, 3, 2, 1] as $n)
                                    <input type="radio" name="rating" id="rate{{ $n }}" value="{{ $n }}" @checked((int) old('rating') === $n) required>
                                    <label for="rate{{ $n }}" title="{{ $n }} bintang"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="visually-hidden">{{ $n }} bintang</span></label>
                                @endforeach
                            </div>
                            <span class="rate-teks" id="rateTeks" aria-live="polite">Beri rating</span>
                        </fieldset>
                    </div>
                    <div class="composer-kanan">
                        <span class="composer-hitung" id="composerHitung" aria-hidden="true">0/2000</span>
                        <button type="submit" id="submitBtn" class="btn btn-ns-primary composer-kirim"><i class="fa-solid fa-paper-plane me-2" aria-hidden="true"></i>Bagikan</button>
                    </div>
                </div>

                <div class="composer-progres d-none" id="uploadProgress" aria-hidden="true">
                    <div class="composer-progres-bar" id="uploadProgressBar"></div>
                </div>
                <p class="visually-hidden" id="composerStatus" role="status" aria-live="polite"></p>
            </div>
        </div>
    </form>
@endguest
