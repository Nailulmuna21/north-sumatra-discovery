# North Sumatra Discovery

Portal web untuk menjelajahi **kuliner, budaya, dan destinasi Sumatera Utara**, dilengkapi
fitur komunitas (pengunjung membagikan pengalaman berupa foto, cerita, dan rating), ulasan
kuliner, favorit, pencarian, serta panel admin.

## Teknologi

| Bagian    | Yang dipakai                                                      |
|-----------|-------------------------------------------------------------------|
| Backend   | PHP 8.3+, Laravel 13 (Blade)                                      |
| Database  | MySQL / MariaDB                                                   |
| Frontend  | Bootstrap 5.3 dan Font Awesome 6 (CDN), CSS dan JavaScript native |

## Struktur folder

```
north-sumatra-discovery/
├── app/                 Controller, Model, Request, Middleware, Support
├── bootstrap/           Bootstrap aplikasi Laravel
├── config/              Konfigurasi (situs.php, video.php, admin.php, dst.)
├── database/
│   ├── migrations/      Struktur tabel
│   └── seeders/         Data awal (daerah, kuliner, budaya, destinasi, berita, akun)
├── public/              Satu-satunya folder yang diakses web server
│   ├── index.php        Front controller
│   ├── assets/          css/, js/, img/
│   └── storage/         Foto kiriman pengunjung (dibuat saat aplikasi berjalan)
├── resources/views/     Tampilan Blade (layouts, partials, components, halaman)
├── routes/web.php       Daftar route
├── storage/             Log, cache, sesi
├── .env.example         Contoh konfigurasi
├── artisan              Perintah CLI Laravel
└── composer.json
```

## Cara menjalankan (lokal)

Kebutuhan: PHP 8.3+, Composer, MySQL/MariaDB.

```bash
# 1. Pasang dependensi
composer install

# 2. Salin konfigurasi lalu isi DB_DATABASE, DB_USERNAME, DB_PASSWORD
cp .env.example .env
php artisan key:generate

# 3. Buat database kosong (sesuai DB_DATABASE), lalu jalankan migrasi + data awal
php artisan migrate --seed

# 4. Jalankan server
php artisan serve
```

Buka http://localhost:8000.

Akun contoh dari seeder (nilainya diatur di blok `SEED_*` pada `.env`):

| Peran | Email                  | Kata sandi |
|-------|------------------------|------------|
| Admin | admin@nsdiscovery.test | admin1234  |
| User  | user@nsdiscovery.test  | user1234   |

Panel admin ada di `/admin`.

## Catatan
- Foto kiriman pengunjung disimpan di `public/storage/pengalaman/`. Folder ini harus bisa
  ditulis oleh web server.
