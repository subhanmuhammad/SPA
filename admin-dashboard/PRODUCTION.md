# Zenova Spa — Panduan Production

## 1. Server

- PHP 8.4+ dengan ekstensi standar Laravel (pdo_mysql, mbstring, openssl, gd).
- MySQL 8 / MariaDB 10.4+, **tidak terbuka ke internet** (bind ke localhost atau jaringan privat).
- HTTPS wajib (mis. Let's Encrypt). Arahkan document root ke folder `public/`.
- Firewall: hanya port 80/443 (dan SSH) yang terbuka.

## 2. `.env` production

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda
APP_TIMEZONE=Asia/Makassar

DB_HOST=127.0.0.1
DB_DATABASE=zenova
DB_USERNAME=zenova_app        # user khusus, bukan root
DB_PASSWORD=<password kuat>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

SUPERADMIN_NAME=...
SUPERADMIN_EMAIL=...
SUPERADMIN_PASSWORD=<password kuat, ganti setelah login pertama>

BOOKING_BUFFER_MINUTES=0

BACKUP_MYSQLDUMP_PATH=mysqldump
BACKUP_PASSPHRASE=<minimal 16 karakter, simpan juga di luar server>
BACKUP_KEEP_DAYS=14
```

Jangan pernah commit file `.env`.

## 3. Deploy

```
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate        # hanya sekali, saat pertama
php artisan migrate --force
php artisan db:seed --force     # pertama kali: super admin, katalog PRD, metode pembayaran
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Data demo (Terapis Demo, Area Demo) hanya dibuat di `APP_ENV=local`, tidak di production.

## 4. Cron (scheduler + backup harian 02.00)

```
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Backup & restore

Backup manual: `php artisan backup:database` → `storage/app/backups/zenova-YYYYMMDD-HHMMSS.sql.gz.enc`
(terenkripsi AES-256). Salin juga ke penyimpanan di luar server.

Restore:

```
BACKUP_PASSPHRASE='...' openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE \
  -in zenova-YYYYMMDD-HHMMSS.sql.gz.enc | gunzip | mysql -u <user> -p zenova
```

Uji restore secara berkala ke database terpisah.

## 6. Keamanan yang sudah aktif

- Header keamanan di semua respons; Content-Security-Policy ketat saat `APP_ENV=production`; HSTS saat HTTPS.
- Login dibatasi 5 percobaan, sesi diperbarui saat login dan dihapus saat logout; akun nonaktif langsung keluar.
- Booking, cek booking, dan pembayaran dibatasi laju (rate limit).
- Audit log untuk login, booking, jadwal, harga, pembayaran, refund, akun, cabang, dan konfigurasi.

## 7. Sebelum go-live

- Ganti data placeholder cabang (`[Nama Cabang]`, `[Alamat cabang]`, `[Nomor telepon]`) dari menu **Cabang**.
- Isi durasi layanan yang masih "Belum diatur" (layanan tanpa durasi tidak bisa dibooking).
- Lengkapi komponen paket yang belum terhubung ("Pijat" di paket promo, "Keretek").
- Tambahkan terapis, jadwal kerja, area Home Care, dan akun Admin cabang.
- Minta logo resolusi tinggi (logo saat ini 150×150 px).
- **Beli Credit Removal License template Terapia** (https://htmlcodex.com/credit-removal). Kredit HTML Codex/ThemeWagon sudah dihapus dari footer atas keputusan klien, jadi lisensi ini wajib dibeli sebelum website dipublikasikan.
- **Foto Wikimedia Commons belum diberi atribusi yang terlihat**: tautan "Kredit foto" dihapus dari footer atas permintaan klien, halaman `/kredit-foto` masih ada tapi tidak ditautkan. Sebelum go-live, ganti 4 foto di `public/images/site` dengan foto milik Zenova, atau tautkan kembali `/kredit-foto`.
- MFA untuk Super Admin belum diaktifkan (ditunda sesuai keputusan).
