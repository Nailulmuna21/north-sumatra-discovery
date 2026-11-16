/* navbar-v2.js — "Komunitas" di navbar.
   - Di Beranda: menu Komunitas menyala saat section #komunitas terlihat (Beranda padam), klik = gulir halus tanpa muat ulang.
   - Di halaman lain: link biasa ke Beranda#komunitas.
   - Di layar kecil: menu hamburger ditutup setelah memilih Komunitas. */
document.addEventListener('DOMContentLoaded', () => {
    const link = document.querySelector('[data-nav-komunitas]');
    const seksi = document.getElementById('komunitas');
    if (!link) return;
    const beranda = document.querySelector('[data-nav-beranda]');
    const tutupMenu = () => {
        const menu = document.getElementById('navbarNav');
        if (menu && menu.classList.contains('show') && window.bootstrap) bootstrap.Collapse.getOrCreateInstance(menu).hide();
    };
    if (!seksi) return;   // bukan Beranda
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const kurangGerak = matchMedia('(prefers-reduced-motion: reduce)').matches;
        seksi.scrollIntoView({ behavior: kurangGerak ? 'auto' : 'smooth', block: 'start' });
        history.replaceState(null, '', '#komunitas');
        tutupMenu();
    });
    if (!('IntersectionObserver' in window)) return;
    const awalBeranda = beranda && beranda.classList.contains('active');
    new IntersectionObserver(([e]) => {
        link.classList.toggle('active', e.isIntersecting);
        link.setAttribute('aria-current', e.isIntersecting ? 'location' : 'false');
        if (beranda && awalBeranda) beranda.classList.toggle('active', !e.isIntersecting);
    }, { rootMargin: '-35% 0px -45% 0px' }).observe(seksi);
});
