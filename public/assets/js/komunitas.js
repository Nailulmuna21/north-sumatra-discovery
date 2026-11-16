/* komunitas.js — Composer "ala media sosial" + galeri mini di carousel Komunitas (Beranda).
   Menggantikan pengalaman.js (modal). Fitur:
   - jenis Kuliner/Destinasi: mengganti warna composer, placeholder, dan sumber saran nama
   - saran nama tempat (datalist, dari /pengalaman/saran) — hanya saran, boleh mengetik bebas
   - rating bintang (radio) + teks bantu, penghitung karakter, textarea yang tumbuh otomatis
   - foto opsional (maks 4): pilih / seret-lepas, pratinjau, hapus satu per satu, validasi tipe & ukuran
   - kirim via XHR + CSRF dengan progres, lalu slide baru (HTML dari server) masuk ke carousel
   - galeri mini: klik thumbnail mengganti foto utama (delegasi, jadi slide baru ikut bekerja)
   Tanpa JS, form tetap bisa dikirim sebagai form biasa (progressive enhancement). */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const $ = (id) => document.getElementById(id);

    /* ---------- galeri mini di slide (berlaku untuk slide lama & baru) ---------- */
    document.addEventListener('click', (e) => {
        const thumb = e.target.closest('[data-cerita-thumb]');
        if (!thumb) return;
        const media = thumb.closest('.cerita-media');
        const utama = media && media.querySelector('[data-cerita-utama]');
        if (!utama) return;
        utama.src = thumb.dataset.ceritaThumb;
        media.querySelectorAll('[data-cerita-thumb]').forEach((t) => {
            const aktif = t === thumb;
            t.classList.toggle('is-aktif', aktif);
            t.setAttribute('aria-pressed', aktif ? 'true' : 'false');
        });
    });

    const form = $('formPengalaman');
    if (!form) return;   // tamu: hanya galeri yang aktif

    const MAKS = Number(form.dataset.maksFoto) || 4;
    const BATAS = 5 * 1024 * 1024;          // batas SETELAH diperkecil (sama dengan aturan server)
    const BATAS_ASLI = 40 * 1024 * 1024;    // foto asli dari HP boleh besar; diperkecil di browser sebelum dikirim
    const SISI_MAKS = 1600;                 // sisi terpanjang hasil (px)
    const BATAS_RINGAN = 700 * 1024;        // foto yang sudah <= ini dan <= SISI_MAKS dikirim apa adanya
    let sibuk = 0;                          // jumlah foto yang sedang diperkecil
    const TIPE = ['image/jpeg', 'image/png', 'image/webp'];
    const cerita = $('composerCerita');
    const tempat = $('composerTempat');
    const hitung = $('composerHitung');
    const galat = $('pengalamanGalat');
    const input = $('inputFotoPengalaman');
    const grid = $('composerPratinjau');
    const status = $('composerStatus');
    let berkas = [];   // File[] yang akan dikirim (urutan = urutan foto)

    const tampilGalat = (pesan) => {
        galat.replaceChildren(...[].concat(pesan).map((t) => Object.assign(document.createElement('div'), { textContent: t })));
        galat.classList.remove('d-none');
    };
    const bersihGalat = () => { galat.classList.add('d-none'); galat.replaceChildren(); };

    /* ---------- jenis: warna + placeholder + saran ---------- */
    const jenisSekarang = () => (form.querySelector('input[name="jenis"]:checked') || {}).value || 'destinasi';
    function terapkanJenis() {
        const j = jenisSekarang();
        form.classList.toggle('dom-kuliner', j === 'kuliner');
        form.classList.toggle('dom-destinasi', j !== 'kuliner');
        tempat.placeholder = j === 'kuliner' ? tempat.dataset.phKuliner : tempat.dataset.phDestinasi;
        $('saranTempat').replaceChildren();
        saran();
    }
    form.querySelectorAll('input[name="jenis"]').forEach((r) => r.addEventListener('change', terapkanJenis));

    let tundaSaran, kontrolSaran;
    function saran() {
        clearTimeout(tundaSaran);
        const q = tempat.value.trim();
        if (q.length < 2) { $('saranTempat').replaceChildren(); return; }
        tundaSaran = setTimeout(async () => {
            kontrolSaran?.abort();
            kontrolSaran = new AbortController();
            try {
                const url = `${form.dataset.saranUrl}?jenis=${encodeURIComponent(jenisSekarang())}&q=${encodeURIComponent(q)}`;
                const res = await fetch(url, { headers: { Accept: 'application/json' }, signal: kontrolSaran.signal });
                if (!res.ok) return;
                const daftar = await res.json();
                $('saranTempat').replaceChildren(...daftar.map((nama) => Object.assign(document.createElement('option'), { value: nama })));
            } catch (_) { /* saran bersifat opsional: abaikan galat / dibatalkan */ }
        }, 250);
    }
    tempat.addEventListener('input', saran);

    /* ---------- rating ---------- */
    const TEKS_RATING = ['Beri rating', 'Kurang', 'Biasa saja', 'Lumayan', 'Bagus', 'Luar biasa'];
    form.querySelectorAll('input[name="rating"]').forEach((r) => r.addEventListener('change', () => {
        $('rateTeks').textContent = TEKS_RATING[Number(r.value)] || TEKS_RATING[0];
    }));
    const terpilih = form.querySelector('input[name="rating"]:checked');
    if (terpilih) $('rateTeks').textContent = TEKS_RATING[Number(terpilih.value)];

    /* ---------- cerita: tumbuh otomatis + penghitung ---------- */
    function ukurCerita() {
        cerita.style.height = 'auto';
        cerita.style.height = Math.min(cerita.scrollHeight, 280) + 'px';
        const n = cerita.value.length;
        hitung.textContent = `${n}/2000`;
        hitung.classList.toggle('is-hampir', n > 1800);
    }
    cerita.addEventListener('input', ukurCerita);
    cerita.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); form.requestSubmit(); }
    });
    ukurCerita();

    /* ---------- foto ---------- */
    $('composerFotoBtn').addEventListener('click', () => input.click());
    input.addEventListener('change', () => { tambahFoto(Array.from(input.files)); });
    // seret & lepas di seluruh composer
    ['dragenter', 'dragover'].forEach((ev) => form.addEventListener(ev, (e) => {
        if (e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files')) { e.preventDefault(); form.classList.add('is-drag'); }
    }));
    ['dragleave', 'drop'].forEach((ev) => form.addEventListener(ev, (e) => {
        if (ev === 'dragleave' && form.contains(e.relatedTarget)) return;
        form.classList.remove('is-drag');
    }));
    form.addEventListener('drop', (e) => {
        if (!e.dataTransfer || !e.dataTransfer.files.length) return;
        e.preventDefault();
        tambahFoto(Array.from(e.dataTransfer.files));
    });

    /* Foto HP (3-10 MB, 12-50 MP) diperkecil DI BROWSER: sisi terpanjang 1600 px, WebP (cadangan JPEG), orientasi EXIF
       diterapkan, metadata (mis. lokasi GPS) terbuang. Server hosting gratis tidak kuat memproses foto sebesar itu
       (memori & waktu), jadi yang dikirim hanya ratusan KB. Bila browser gagal memproses, file asli dikirim apa adanya. */
    async function muatGambar(f) {
        try { return { sumber: await createImageBitmap(f, { imageOrientation: 'from-image' }), bersih: () => {} }; } catch (_) { /* coba cara lain */ }
        const url = URL.createObjectURL(f);
        const im = new Image();
        im.src = url;
        await im.decode();
        return { sumber: im, bersih: () => URL.revokeObjectURL(url) };
    }
    async function kecilkan(f) {
        try {
            const { sumber, bersih } = await muatGambar(f);
            const p = sumber.naturalWidth || sumber.width, l = sumber.naturalHeight || sumber.height;
            const skala = Math.min(1, SISI_MAKS / Math.max(p, l));
            if (skala === 1 && f.size <= BATAS_RINGAN) { bersih(); sumber.close && sumber.close(); return f; }
            const cv = document.createElement('canvas');
            cv.width = Math.max(1, Math.round(p * skala));
            cv.height = Math.max(1, Math.round(l * skala));
            cv.getContext('2d').drawImage(sumber, 0, 0, cv.width, cv.height);
            bersih(); sumber.close && sumber.close();
            let blob = await new Promise((r) => cv.toBlob(r, 'image/webp', 0.82));
            let ekstensi = 'webp';
            if (!blob || blob.type !== 'image/webp') {            // Safari lama tidak bisa menyandikan WebP
                blob = await new Promise((r) => cv.toBlob(r, 'image/jpeg', 0.85));
                ekstensi = 'jpg';
            }
            cv.width = cv.height = 0;                              // lepaskan memori kanvas
            if (!blob || (skala === 1 && blob.size >= f.size)) return f;
            return new File([blob], (f.name || 'foto').replace(/\.[^.]+$/, '') + '.' + ekstensi, { type: blob.type, lastModified: Date.now() });
        } catch (_) {
            return f;
        }
    }
    async function tambahFoto(baru) {
        bersihGalat();
        const pesan = [];
        const terima = [];
        for (const f of baru) {
            if (berkas.length + terima.length >= MAKS) { pesan.push(`Foto maksimal ${MAKS} buah.`); break; }
            if (!TIPE.includes(f.type)) { pesan.push('File harus berupa gambar (JPG, PNG, WEBP).'); continue; }
            if (f.size > BATAS_ASLI) { pesan.push('Foto terlalu besar (maksimal 40MB sebelum diperkecil).'); continue; }
            terima.push(f);
        }
        if (terima.length) {
            sibuk += terima.length;
            $('submitBtn').disabled = true;
            status.textContent = 'Memproses foto...';
            grid.hidden = false;
            for (const f of terima) {
                const hasil = await kecilkan(f);
                sibuk--;
                if (hasil.size > BATAS) { pesan.push('Ukuran tiap foto maksimal 5MB.'); continue; }
                berkas.push(hasil);
            }
            status.textContent = '';
            if (!sibuk) $('submitBtn').disabled = false;
        }
        if (pesan.length) tampilGalat([...new Set(pesan)]);
        sinkronFoto();
    }
    function sinkronFoto() {
        const dt = new DataTransfer();
        berkas.forEach((f) => dt.items.add(f));
        input.files = dt.files;   // yang dikirim = yang tampil
        grid.querySelectorAll('img').forEach((im) => URL.revokeObjectURL(im.src));
        grid.replaceChildren(...berkas.map((f, i) => {
            const sel = document.createElement('div');
            sel.className = 'composer-foto';
            const im = document.createElement('img');
            im.src = URL.createObjectURL(f);
            im.alt = `Pratinjau foto ${i + 1}`;
            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'composer-foto-hapus';
            hapus.setAttribute('aria-label', `Hapus foto ${i + 1}`);
            hapus.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
            hapus.addEventListener('click', () => { berkas.splice(i, 1); bersihGalat(); sinkronFoto(); });
            sel.append(im, hapus);
            return sel;
        }));
        grid.hidden = berkas.length === 0;
        $('composerFotoBtn').disabled = berkas.length >= MAKS;
    }

    /* ---------- kirim ---------- */
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        bersihGalat();
        if (!form.reportValidity()) return;
        if (sibuk) { tampilGalat('Foto masih diproses. Tunggu sebentar lalu tekan Bagikan lagi.'); return; }
        const tombol = $('submitBtn');
        const wrap = $('uploadProgress'), bar = $('uploadProgressBar');
        tombol.disabled = true;
        wrap.classList.remove('d-none');
        bar.style.width = '0%';
        status.textContent = 'Mengirim ceritamu...';
        const selesai = () => { tombol.disabled = false; setTimeout(() => { wrap.classList.add('d-none'); bar.style.width = '0%'; }, 400); };

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
        xhr.upload.onprogress = (ev) => { if (ev.lengthComputable) bar.style.width = Math.round((ev.loaded / ev.total) * 90) + '%'; };
        xhr.onload = () => {
            let data = {};
            try { data = JSON.parse(xhr.responseText); } catch (_) { /* biarkan kosong */ }
            if (xhr.status === 201) {
                bar.style.width = '100%';
                status.textContent = 'Ceritamu sudah dibagikan.';
                tambahSlide(data.html);
                const angka = $('angkaCerita');
                if (angka) angka.textContent = String((parseInt(angka.textContent, 10) || 0) + 1);
                const jenis = jenisSekarang();
                form.reset();
                form.querySelector(`input[name="jenis"][value="${jenis}"]`).checked = true;   // jenis dipertahankan
                berkas = []; sinkronFoto(); terapkanJenis(); ukurCerita();
                $('rateTeks').textContent = TEKS_RATING[0];
                selesai();
                return;
            }
            if (xhr.status === 401) { window.location.href = document.querySelector('a[href$="/masuk"]')?.href || '/masuk'; return; }
            if (xhr.status === 422 && data.errors) tampilGalat(Object.values(data.errors).flat());
            else if (xhr.status === 429) tampilGalat('Terlalu banyak kiriman. Tunggu sebentar lalu coba lagi.');
            else if (xhr.status === 419) tampilGalat('Sesi halaman sudah habis. Muat ulang halaman lalu coba lagi.');
            else if (xhr.status === 413) tampilGalat('Ukuran foto terlalu besar untuk server. Kurangi jumlah foto lalu coba lagi.');
            else tampilGalat((data.message || 'Gagal membagikan pengalaman.') + ` (kode ${xhr.status})`);
            selesai();
        };
        xhr.onerror = () => { tampilGalat('Koneksi terputus atau server menolak unggahan. Periksa internetmu atau coba dengan foto lebih sedikit.'); selesai(); };
        xhr.send(new FormData(form));   // memuat _token, jenis, rating, cerita, daerah_id, nama_tempat, foto[]
    });

    /* ---------- slide baru ---------- */
    function tambahSlide(html) {
        if (!html) return;
        const inner = $('carouselPengalamanInner');
        inner.querySelector('[data-kosong]')?.remove();
        inner.querySelectorAll('.carousel-item').forEach((el) => el.classList.remove('active'));
        const wadah = document.createElement('div');
        wadah.innerHTML = html.trim();                       // HTML sudah di-escape oleh Blade di server
        const slide = wadah.firstElementChild;
        slide.classList.add('active');
        inner.appendChild(slide);
        bootstrap.Carousel.getOrCreateInstance($('carouselPengalaman')).to(inner.children.length - 1);
        $('carouselPengalaman').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
