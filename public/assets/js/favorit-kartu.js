/* favorit-kartu.js — tombol hati (simpan ke favorit) di kartu kuliner Beranda.
   - Delegasi klik di document: kartu hasil salinan marquee (looping) & kartu yang dibangun ulang filter chip ikut bekerja.
   - Semua tombol dengan URL yang sama (kartu asli + salinan loop) disinkronkan setelah server menjawab.
   - Tamu: server membalas 401 -> diarahkan ke halaman masuk. Tanpa JS tombol tidak berfungsi (hanya tampilan). */
document.addEventListener('click', async (e) => {
    const tombol = e.target.closest('.card-heart');
    if (!tombol) return;
    e.preventDefault();
    e.stopPropagation();
    if (tombol.dataset.sibuk) return;
    tombol.dataset.sibuk = '1';
    try {
        const res = await fetch(tombol.dataset.favoritUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (res.status === 401) { window.location.href = tombol.dataset.masukUrl; return; }
        if (res.status === 419) { window.location.reload(); return; }
        if (!res.ok) throw new Error('gagal');
        const { favorit } = await res.json();
        // asli + salinan loop (marquee.js menggandakan kartu) + kartu di tempat lain dengan URL sama
        document.querySelectorAll('.card-heart').forEach((t) => {
            if (t.dataset.favoritUrl !== tombol.dataset.favoritUrl) return;
            t.classList.toggle('is-aktif', favorit);
            t.setAttribute('aria-pressed', favorit ? 'true' : 'false');
            const ikon = t.querySelector('i');
            ikon.classList.toggle('fa-solid', favorit);
            ikon.classList.toggle('fa-regular', !favorit);
            if (favorit) { t.classList.add('is-pop'); setTimeout(() => t.classList.remove('is-pop'), 450); }
        });
    } catch (_) {
        tombol.classList.add('is-gagal');
        setTimeout(() => tombol.classList.remove('is-gagal'), 700);
    } finally {
        delete tombol.dataset.sibuk;
    }
});
