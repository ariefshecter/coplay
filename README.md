# coplay
# Laravel Multi-Vendor E-Commerce Application

Proyek ini adalah aplikasi e-commerce multi-vendor yang dibangun menggunakan framework Laravel.

## Prasyarat

Sebelum memulai, pastikan Anda sudah memiliki perangkat lunak berikut terinstal di komputer Anda:

* **PHP:** Versi yang kompatibel dengan proyek Laravel (biasanya versi terbaru lebih baik).
* **Composer:** Manajer dependensi untuk PHP.
* **Node.js dan NPM:** Untuk manajemen paket JavaScript dan menjalankan skrip build.
* **Server Database MySQL atau MariaDB:** Untuk menyimpan data aplikasi.
* **Git:** (Opsional, jika Anda ingin mengkloning repositori)

## Langkah-Langkah Instalasi

1.  **Unduh atau Kloning Proyek:**
    * **Menggunakan Git (disarankan):**
        Buka terminal atau command prompt Anda, lalu jalankan perintah berikut untuk mengkloning repositori proyek dari GitHub:
        ```bash
        git clone https://github.com/ariefshecter/coplay.git
        ```
    * **Mengunduh ZIP:**
        Jika Anda tidak menggunakan Git, Anda bisa mengunduh proyek sebagai file ZIP langsung dari halaman GitHub proyek tersebut (`https://github.com/ariefshecter/coplay.git`). Setelah diunduh, ekstrak file ZIP tersebut ke direktori yang Anda inginkan.

2.  **Masuk ke Direktori Proyek dan Instal Dependensi Composer:**
    * Buka terminal atau command prompt Anda.
    * Arahkan (menggunakan perintah `cd`) ke direktori root proyek yang baru saja Anda kloning atau ekstrak. Misalnya, jika Anda mengkloningnya ke dalam folder `coplay`, maka perintahnya adalah:
        ```bash
        cd coplay
        ```
    * Setelah berada di direktori root proyek, jalankan perintah berikut untuk menginstal semua dependensi PHP yang dibutuhkan oleh proyek (didefinisikan dalam file `composer.json`):
        ```bash
        composer install
        ```
        Proses ini mungkin memerlukan beberapa waktu tergantung pada kecepatan internet Anda.

3.  **Instal Dependensi NPM dan Build Aset Frontend:**
    * Masih di terminal atau command prompt, di dalam direktori root proyek, jalankan perintah berikut untuk menginstal semua paket JavaScript yang dibutuhkan (didefinisikan dalam file `package.json`):
        ```bash
        npm install
        ```
    * **Hanya jika Anda menghadapi masalah/error saat `npm install`**, jalankan perintah berikut untuk mencoba memperbaiki masalah tersebut:
        ```bash
        npm audit fix
        ```
    * Setelah instalasi dependensi NPM selesai, jalankan perintah berikut untuk mengkompilasi aset frontend (seperti file CSS dan JavaScript):
        ```bash
        npm run build
        ```

4.  **Buat Database MySQL dan Impor Data:**
    * Buat sebuah database baru di MySQL Anda. Anda bisa menggunakan phpMyAdmin atau perintah SQL langsung. Beri nama database tersebut `coplay_database`.
    * Setelah database dibuat, impor file SQL dump `coplay_database` ke dalam database `coplay_database` yang baru saja Anda buat. File SQL dump ini biasanya berada di dalam direktori proyek (cari file dengan ekstensi `.sql` yang berisi struktur dan data database awal). Lokasi pasti file dump ini mungkin perlu Anda cari di dalam folder proyek yang sudah diunduh/dikloning, seringkali diletakkan di folder `database` atau folder utama.

5.  **Konfigurasi File Lingkungan (.env):**
    * Di dalam direktori root proyek, Anda akan menemukan file bernama `.env.example`. Salin file ini dan beri nama salinannya menjadi `.env`.
    * Buka file `.env` tersebut menggunakan editor teks.
    * **Konfigurasi Database:** Cari baris-baris berikut dan sesuaikan dengan kredensial database MySQL Anda:
        ```env
        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1  // atau alamat host database Anda
        DB_PORT=3306      // atau port database Anda
        DB_DATABASE=multivendor_ecommerce // pastikan nama database sesuai dengan yang Anda buat
        DB_USERNAME=root  // ganti dengan username database Anda
        DB_PASSWORD=      // ganti dengan password database Anda (kosongkan jika tidak ada password)
        ```
    * **Konfigurasi Lainnya:** Periksa juga pengaturan lain di file `.env` seperti `APP_URL` (biasanya diatur ke `http://127.0.0.1:8000` untuk pengembangan lokal), konfigurasi email, dan kunci aplikasi (`APP_KEY`). Jika `APP_KEY` kosong, Anda bisa membuatnya dengan menjalankan perintah `php artisan key:generate` di terminal (pastikan Anda masih berada di direktori root proyek).

6.  **Jalankan Server Pengembangan Laravel:**
    * Kembali ke terminal atau command prompt Anda (pastikan Anda masih berada di direktori root proyek).
    * Jalankan perintah berikut untuk memulai server pengembangan bawaan Laravel:
        ```bash
        php artisan serve
        ```
    * Secara default, server akan berjalan di `http://127.0.0.1:8000`.

7.  **Akses Aplikasi di Browser:**
    * Buka browser web Anda.
    * Untuk mengakses **bagian Frontend (Tampilan Pengguna)** aplikasi, kunjungi: `http://127.0.0.1:8000`
    * Untuk mengakses **Panel Admin**, kunjungi: `http://127.0.0.1:8000/admin/login`

## Kredensial Akun Siap Pakai

Anda dapat menggunakan kredensial berikut untuk masuk dan mencoba aplikasi:

* **Superadmin (untuk mengakses Panel Admin):**
    * Email: `admin@admin.com`
    * Password: `123456`
* **Vendor (untuk mengakses Panel Admin):**
    * Email: `yasser@admin.com`
    * Password: `123456`
* **Pengguna (untuk mengakses Frontend):**
    * Email: `ibrahim@gmail.com`
    * Password: `123456`

## Catatan Tambahan

* Pastikan semua perintah di atas dijalankan dari direktori root proyek Anda.
* Jika Anda mengalami masalah terkait permission (izin), pastikan direktori `storage` dan `bootstrap/cache` memiliki izin tulis yang benar untuk server web Anda.
* Periksa log error Laravel (di `storage/logs/laravel.log`) jika Anda menemui halaman error atau perilaku yang tidak diharapkan.

---
Semoga berhasil dengan instalasi proyeknya!
