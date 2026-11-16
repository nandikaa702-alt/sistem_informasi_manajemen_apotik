  # Sistem Informasi Manajemen Apotek — Laravel + MySQL + Tailwind

Paket ini adalah **starter code / overlay** untuk proyek Laravel yang sudah dibuat. Paket tidak menyertakan framework Laravel, `vendor/`, atau file `.env`.

## Fitur dari proposal
Login, dashboard, CRUD obat, kategori, pemasok, stok, transaksi penjualan, riwayat transaksi, peringatan stok menipis, monitoring kedaluwarsa, laporan penjualan/stok, dan manajemen pengguna.

Tambahan yang disiapkan: pencatatan pembelian dari pemasok dan mutasi stok.

## Persyaratan
- PHP 8.1+ (disarankan PHP 8.3)
- Composer
- MySQL
- Node.js + npm
- Laravel 10

## Cara memasang
1. Buat proyek Laravel:
   ```bash
   composer create-project laravel/laravel apotek
   cd apotek
   ```
2. Salin folder `app/`, `database/`, `resources/`, file `routes/web.php`, dan konfigurasi terkait dari paket ini ke proyek Laravel. Jika diminta, gabungkan file, jangan menimpa file penting yang sudah Anda ubah.
3. Buat database MySQL bernama `db_apotek`, lalu sesuaikan `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_apotek
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Pasang dependency dan jalankan migrasi:
   ```bash
   composer install
   php artisan migrate
   php artisan db:seed
   npm run build
   php artisan serve
   ```
   `db:seed` mengisi data contoh untuk demo/development dan aman dijalankan ulang. Jangan jalankan pada database produksi.
5. Buka `/setup` untuk membuat akun admin pertama. Halaman setup otomatis ditutup setelah akun pengguna pertama tersimpan.
6. Masuk melalui `/login`; admin dapat membuat akun apoteker dan kasir pada menu Manajemen Pengguna.

## Hak akses
- `admin`: semua modul termasuk manajemen pengguna.
- `apoteker`: dashboard, obat, kategori, pemasok, stok, penjualan, dan pembelian; tidak dapat mengelola akun pengguna.
- `kasir`: daftar obat dan transaksi penjualan; tidak dapat mengakses dashboard atau modul operasional lainnya.

## Catatan implementasi penting
- Penjualan menggunakan database transaction dan mengunci baris stok agar stok tidak berkurang secara bersamaan secara tidak konsisten.
- Harga disimpan sebagai `DECIMAL`; jumlah stok menggunakan bilangan bulat.
- Obat yang kedaluwarsa atau stok kosong tidak dapat dijual.
- Penambahan stok manual, stok awal, penjualan, dan pembelian terkonfirmasi dicatat dalam riwayat mutasi dengan stok sebelum/sesudah.
- Batas stok minimum ditentukan per obat (default 10); peringatan kedaluwarsa mencakup 90 hari ke depan.
- Pembelian berstatus menunggu konfirmasi dan baru menambah stok setelah dikonfirmasi.
- Satu pesanan pembelian dapat berisi beberapa obat; subtotal per item dijumlahkan server-side menjadi total pesanan.
- Sistem ini adalah aplikasi administrasi apotek, bukan sistem diagnosis atau rekomendasi/pemberian resep.
- Sebelum digunakan untuk operasional nyata, tambahkan pengujian otomatis, backup database, HTTPS, audit log, kebijakan akses yang ditinjau, dan validasi alur sesuai SOP apotek.

## Route utama
- `/dashboard`
- `/login`, `/logout`, `/setup` (admin pertama saja)
- `/medicines`
- `/categories`
- `/suppliers`
- `/stock` dan `/stock/history`
- `/suppliers`
- `/sales`
- `/sales/create`
- `/sales/medicine-search`
- `/sales/{id}/receipt`
- `/purchases`
- `/purchases/create`
- `/purchases/orders/{id}/confirm`
- `/reports` (admin/apoteker; filter tanggal, ekspor PDF dan XLSX)
- `/reports`
- `/users` (admin)
- `/alerts/low-stock`
- `/alerts/expiring`
apotek-management/
│
├── app/
│   ├── Models/
│   ├── Http/Controllers/
│   └── Services/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   └── views/
│       ├── dashboard/
│       ├── obat/
│       ├── supplier/
│       ├── pembelian/
│       ├── penjualan/
│       └── laporan/
│
├── routes/
│   └── web.php
│
├── public/
├── tests/
└── .env