/*
 * marquee.js — strip kartu yang bergeser otomatis dari samping, looping mulus (Beranda).
 *
 * Markup (lihat beranda.blade.php):
 *   <div class="mq [mq-kanan]" data-marquee>
 *     <div class="mq-track"><div class="mq-group"> <div class="mq-item" data-daerah="slug">...kartu...</div> ... </div></div>
 *   </div>
 *
 * Cara kerja:
 *   1. Item asli dari server disimpan, lalu track dibangun ulang: 1 grup yang cukup lebar
 *      (item diulang sampai melewati lebar layar) + 1 salinan identik (aria-hidden) -> animasi -50% mulus.
 *   2. Durasi dihitung dari lebar grup, jadi kecepatan selalu konstan (px per detik).
 *   3. Chip filter daerah (chip-filter.js mengirim event "chip:filter") menyaring item lalu membangun ulang.
XX (ditunda). Reduced-motion / tanpa JS -> strip tetap bisa digeser manual.
 */
(() => {
    'use strict';

    const KECEPATAN = 42;   // piksel per detik. GANTI DI SINI untuk mempercepat / memperlambat
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const strips = Array.from(document.querySelectorAll('[data-marquee]'));
    if (!strips.length) return;

    strips.forEach((root) => {
        const track = root.querySelector('.mq-track');
        const asli = Array.from(track.querySelectorAll('.mq-item')).map((el) => el.cloneNode(true));
        if (!asli.length) return;
        let daerah = 'semua';
        let labelDaerah = '';
        const kosongEl = root.dataset.mqKosong ? document.querySelector(root.dataset.mqKosong) : null;
        const infoEl = root.dataset.mqInfo ? document.querySelector(root.dataset.mqInfo) : null;
        const satuan = root.dataset.mqSatuan || 'kartu';
        function tulisInfo(n) {
            if (kosongEl) kosongEl.hidden = n > 0;
            if (infoEl) infoEl.textContent = n > 0
                ? `${n} ${satuan} ${daerah === 'semua' ? 'dari semua daerah' : 'di ' + labelDaerah}`
                : `Tidak ada ${satuan} di ${labelDaerah}`;
        }

        const cocok = (el) => daerah === 'semua' || el.dataset.daerah === daerah || el.dataset.daerah === 'semua';

        function bangun() {
            if (reduce.matches) {
                // tanpa animasi: strip dibiarkan bisa digeser manual; filter cukup menyembunyikan item
                root.classList.remove('is-live');
                let tampil = 0;
                track.querySelectorAll('.mq-item').forEach((el) => { const ok = cocok(el); el.style.display = ok ? '' : 'none'; if (ok) tampil++; });
                root.classList.toggle('is-kosong', tampil === 0);
                tulisInfo(tampil);
                return;
            }
            const daftar = asli.filter(cocok);
            root.classList.toggle('is-kosong', daftar.length === 0);
            if (!daftar.length) return;

            track.innerHTML = '';
            root.classList.remove('is-statis');   // bug lama: kelas ini menempel & mematikan animasi pada filter berikutnya
            const grup = document.createElement('div');
            grup.className = 'mq-group';
            track.appendChild(grup);
            root.classList.add('is-live');

            let ulang = 0;
            const tambah = () => { daftar.forEach((el) => grup.appendChild(el.cloneNode(true))); ulang++; };
            tambah();
            // Hanya 1 kartu: diulang akan tampak kembar -> statis, rata kiri. 2+ kartu tetap bergulir (diulang sampai penuh).
            if (daftar.length < 2 && grup.getBoundingClientRect().width < root.clientWidth) {
                root.classList.remove('is-live');
                root.classList.add('is-statis');
                return;
            }
            while (grup.getBoundingClientRect().width < root.clientWidth && ulang < 12) tambah();

            const salinan = grup.cloneNode(true);
            salinan.setAttribute('aria-hidden', 'true');
            salinan.querySelectorAll('a, button').forEach((el) => { el.tabIndex = -1; });
            track.appendChild(salinan);

            const lebar = grup.getBoundingClientRect().width;
            root.style.setProperty('--mq-dur', Math.max(18, lebar / KECEPATAN).toFixed(1) + 's');
        }

        // chip filter (hanya yang menunjuk strip ini lewat data-chip-marquee="#id")
        document.addEventListener('chip:filter', (e) => {
            const sel = e.detail.grup && e.detail.grup.dataset.chipMarquee;
            if (!sel || document.querySelector(sel) !== root) return;
            daerah = e.detail.daerah || 'semua';
            labelDaerah = e.detail.label || daerah;
            bangun();
        });

        let tunda;
        let lebarTerakhir = window.innerWidth;
        window.addEventListener('resize', () => {
            if (window.innerWidth === lebarTerakhir) return;   // abaikan resize vertikal (bar alamat mobile)
            lebarTerakhir = window.innerWidth;
            clearTimeout(tunda);
            tunda = setTimeout(bangun, 200);
        });
        reduce.addEventListener?.('change', bangun);

        // tunggu gambar pertama agar lebar kartu pasti final, tapi jangan menahan lebih dari sebentar
        bangun();
        window.addEventListener('load', bangun, { once: true });
    });
})();
