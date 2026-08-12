# ☀️ Helios Solar Tracker - Web Dashboard & API

Sebuah sistem *dashboard* cerdas berbasis web dan API untuk memantau performa *Solar Tracker* secara *real-time*. Proyek ini merupakan tulang punggung (*backend*) dari ekosistem riset **Helios Solar Tracker**, yang menerima data langsung dari sensor IoT (ESP32) dan menampilkannya dalam bentuk grafik analisis yang intuitif.

---

## 🌟 Fitur Utama

- **Real-time Monitoring:** Menerima aliran data sensor (Tegangan, Arus, Daya, posisi Azimuth/Elevasi, dan nilai LDR) setiap detik dari ESP32.
- **Interactive Dashboard:** Menampilkan data dalam bentuk grafik garis (*line chart*) dinamis dan meteran digital.
- **Smart Weather Integration:** Terintegrasi dengan Open-Meteo API untuk menampilkan kondisi cuaca lokal di lokasi instalasi panel surya (secara *hardcoded* untuk akurasi tinggi).
- **Data Export:** Menyediakan fitur unduh log sejarah (*historical data*) ke dalam format CSV untuk keperluan analisis riset lebih lanjut.
- **RESTful API:** Menyediakan titik akhir (*endpoints*) stabil bagi ESP32 untuk mengirim data, dan bagi Aplikasi Mobile (Flutter) untuk mengambil data.
- **Dark Mode Support:** Antarmuka pengguna (*UI*) modern yang mendukung mode gelap (*Dark Mode*) untuk kenyamanan visual.

---

## 🏗️ Arsitektur Sistem

Proyek ini dibangun menggunakan pendekatan **Monolithic Architecture**:
- **Framework:** Laravel 10 (PHP 8.2)
- **Database:** MySQL 8.0
- **Frontend Web:** Blade Templating Engine + Vanilla JS + Chart.js
- **Deployment:** Docker & Docker Compose (berjalan di atas Ubuntu VPS)
- **Domain Routing:** Terhubung ke `solaris.bengkelit.id` (menggabungkan *traffic* Web dan API dalam satu pintu).

---

## 📡 Ekosistem Terkait

Proyek repositori web ini terhubung erat dengan dua komponen lain:
1. **IoT Hardware (ESP32):** Kode Arduino (`helios_tracker_esp32.ino`) tertanam di dalam folder `esp32/` pada repositori ini.
2. **Mobile App:** Aplikasi pendamping berbasis Flutter untuk pemantauan portabel (*repository* terpisah).

---

## 🚀 Panduan Instalasi (Development)

Jika Anda ingin menjalankan proyek ini di laptop lokal (*localhost*):

1. **Kloning Repositori:**
   ```bash
   git clone https://github.com/fikditzz/solaris-tracker.git
   cd solaris-tracker
   ```
2. **Instalasi Dependensi PHP:**
   ```bash
   composer install
   ```
3. **Pengaturan Lingkungan (.env):**
   Salin `.env.example` menjadi `.env` lalu sesuaikan konfigurasi *database* lokal Anda.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Migrasi Database:**
   ```bash
   php artisan migrate
   ```
5. **Jalankan Server:**
   ```bash
   php artisan serve
   ```
   *Dashboard* sekarang bisa diakses di `http://127.0.0.1:8000`.

---
*Dikembangkan untuk Riset Solar Tracker Berbasis IoT & AI.*
