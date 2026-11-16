/* Chip filter daerah di Beranda (F03). Disaring di klien tanpa memuat ulang.
   Markup:  <div data-chip-filter data-chip-target=".kuliner-populer-item"> ...tombol [data-filter-daerah]... </div>
   Item     :  elemen target ber-atribut data-daerah="<slug>".
   Mode remote (Destinasi, F12): <div data-chip-filter data-chip-remote="#wadah"> berisi tautan <a href="?daerah=slug">.
   Klik chip -> daftar diganti lewat window.NsLive (live-search.js) tanpa reload; tanpa JS tautan tetap bekerja.
   Mode marquee: <div data-chip-filter data-chip-marquee="#idStrip"> -> kirim event "chip:filter" ke marquee.js.
   Aturan lama dipertahankan: "semua" menampilkan semua; item berdaerah "semua" (Seluruh Wilayah Sumatera Utara) selalu tampil. */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-chip-filter]').forEach((grup) => {
        const item = () => document.querySelectorAll(grup.dataset.chipTarget);
        grup.addEventListener('click', (e) => {
            const chip = e.target.closest('[data-filter-daerah]');
            if (!chip || !grup.contains(chip)) return;
            grup.querySelectorAll('[data-filter-daerah]').forEach((c) => c.classList.toggle('active', c === chip));
            // Dropdown "Daerah lain": tombolnya ikut aktif & menampilkan nama daerah terpilih
            const lain = grup.querySelector('[data-chip-lain]');
            if (lain) {
                const dalam = !!chip.closest('[data-chip-lain]');
                lain.querySelector('[data-chip-lain-tombol]').classList.toggle('active', dalam);
                lain.querySelector('[data-chip-lain-label]').textContent = dalam ? (chip.dataset.label || chip.textContent.trim()) : 'Daerah lain';
            }
            if (grup.dataset.chipMarquee && grup.id) { try { sessionStorage.setItem('ns:chip:' + grup.id, chip.dataset.filterDaerah); } catch (_) {} }

            // Mode marquee (Beranda): strip dibangun ulang oleh marquee.js, bukan disembunyikan satu per satu
            if (grup.dataset.chipMarquee) {
                document.dispatchEvent(new CustomEvent('chip:filter', { detail: { grup, daerah: chip.dataset.filterDaerah, label: chip.dataset.label || chip.textContent.trim() } }));
                return;
            }

            const remote = grup.dataset.chipRemote && document.querySelector(grup.dataset.chipRemote);
            if (remote && window.NsLive && chip.href) {
                e.preventDefault();
                window.NsLive.muat(chip.href, remote);
                return;
            }
            const target = chip.dataset.filterDaerah;
            item().forEach((el) => {
                const cocok = target === 'semua' || el.dataset.daerah === target || el.dataset.daerah === 'semua';
                el.style.display = cocok ? '' : 'none';
            });
        });

        // Pulihkan chip terakhir setelah refresh (hanya mode marquee, per sesi tab)
        if (grup.dataset.chipMarquee && grup.id) {
            let simpan = null;
            try { simpan = sessionStorage.getItem('ns:chip:' + grup.id); } catch (_) {}
            if (simpan && simpan !== 'semua') {
                const chip = Array.from(grup.querySelectorAll('[data-filter-daerah]')).find((c) => c.dataset.filterDaerah === simpan);
                chip && chip.click();
            }
        }
    });
});

/* v2: tombol "Tampilkan semua daerah" di pesan kosong -> klik chip "semua" pada grup yang ditunjuk */
document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-chip-reset]');
    if (!tombol) return;
    const grup = document.querySelector(tombol.dataset.chipReset);
    grup && grup.querySelector('[data-filter-daerah="semua"]')?.click();
});
