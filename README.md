<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Demo lokal

### Memisahkan database development dan demo

Database development yang sudah digunakan aplikasi tetap `erp`. Jangan ubah `.env` development yang ada. Untuk setup demo portfolio, `.env.example` menggunakan koneksi MySQL dengan database `erp_demo`; setelah meng-clone repository, salin `.env.example` ke `.env`, atur kredensial MySQL lokal, dan pastikan `APP_ENV=local`.

Buat database demo secara manual di phpMyAdmin:

1. Buka phpMyAdmin dan masuk ke server MySQL lokal.
2. Pilih tab **Databases**.
3. Isi nama database `erp_demo`.
4. Pilih collation `utf8mb4_unicode_ci`, lalu klik **Create**.
5. Pastikan `.env` pada salinan demo menunjuk ke `DB_CONNECTION=mysql` dan `DB_DATABASE=erp_demo`.

Jangan arahkan salinan development ke `erp_demo` atau salinan demo ke `erp`. Jalankan migration yang tersedia secara manual hanya setelah memeriksa koneksi aktif. Seeder tidak membuat database secara otomatis dan berhenti jika `APP_ENV` bukan `local` atau nama database koneksi aktif bukan `erp_demo`.

### Menjalankan data simulasi

Seeder demo ditujukan hanya untuk database lokal `erp_demo`. Sebelum menjalankannya, pastikan migration yang diperlukan sudah tersedia pada database demo.

Seeder memakai SKU dan nomor transaksi dengan awalan `DEMO-`. Jika salah satu identifier sudah dipakai data yang tidak cocok dengan identitas demo, seeding dibatalkan sebelum ada perubahan. Saldo/material existing dengan riwayat non-demo juga tidak digunakan atau diubah.

Untuk membuat akun login demo, jalankan pada `APP_ENV=local` dan sediakan `DEMO_USER_PASSWORD` hanya di environment proses (jangan commit nilainya atau menaruhnya di dokumentasi). Tanpa password itu, data simulasi tetap dibuat tetapi akun demo dilewati dengan pesan yang jelas. Seeder tidak mengubah akun `demo@example.com` yang sudah ada.

Contoh PowerShell, dari terminal lokal tepercaya:

```powershell
$secret = Read-Host "Password demo" -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
try {
    $env:DEMO_USER_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    php artisan db:seed --class=DemoDataSeeder
} finally {
    Remove-Item Env:DEMO_USER_PASSWORD -ErrorAction SilentlyContinue
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    Remove-Variable secret, pointer
}
```

Atau jalankan `php artisan db:seed` untuk memakai `DatabaseSeeder`. Seeder dapat dijalankan berulang kali dan tidak menghapus data. Ia membuat 4 produk, 10 material, 18 baris BOM, 10 saldo material beserta riwayat movement, 4 rencana beserta snapshot, dan 1 realisasi selesai beserta mutasi material dan barang jadi. Akun login yang dibuat memakai email `demo@example.com` dan password yang Anda sediakan melalui `DEMO_USER_PASSWORD`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
