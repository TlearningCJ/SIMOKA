# SIMOKA – Sistem Monitoring Ketersediaan Kelas

Aplikasi web PHP + MySQL sesuai laporan APSI Kelompok 4 (Bab IV–VI).

## Cara cepat (untuk anggota kelompok)
Syarat: **XAMPP** terpasang di `C:\xampp` dan **Git** (atau download ZIP dari GitHub).
```
git clone <url-repo-ini>
cd simoka
setup.bat
```
`setup.bat` akan menyalakan MySQL, membuat database `simoka` + data contoh, lalu membuka http://localhost:8080.
- Menjalankan lagi **tanpa mereset data**: `setup.bat skipdb`
- Menjalankan `setup.bat` biasa akan **mengembalikan database ke data awal**.
- Jika MySQL dari XAMPP Control Panel sudah menyala, tidak masalah — script tetap jalan.
- Jika port 8080 dipakai, ganti `8080` di baris terakhir `setup.bat`.

## Cara manual (XAMPP)
1. Buka XAMPP Control Panel → Start **MySQL** (Apache opsional).
2. Import database: phpMyAdmin → Import → `database/simoka.sql`
   atau lewat terminal:
   ```
   cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < database\simoka.sql"
   ```
3. Jalankan aplikasi, pilih salah satu:
   - `C:\xampp\php\php.exe -S localhost:8080` (dari folder ini) → buka http://localhost:8080
   - atau salin folder `simoka` ke `C:\xampp\htdocs\` → buka http://localhost/simoka

Pengaturan koneksi database ada di `app.php` (default: root tanpa password).

## Akun uji
| Peran | Email | Kata sandi |
|---|---|---|
| Admin | admin@simoka.test | admin123 |
| PJ Kelas | rani@simoka.test / dimas@simoka.test / fadil@simoka.test | pj12345 |
| Mahasiswa | talitha@simoka.test | mhs12345 (atau tanpa login) |

## Struktur
| File | Use case / FR |
|---|---|
| `index.php` | UC-02, UC-05 – daftar ruangan & cari ruangan kosong (FR-01, 03, 07, 13, 14, 16) |
| `ruangan.php` | UC-03 – detail ruangan, fasilitas, jadwal hari ini (FR-05, 08–12) |
| `jadwal.php` | UC-04 – jadwal per hari/ruangan (FR-02, 06) |
| `login.php`, `logout.php` | UC-01 – autentikasi per peran (NFR-02) |
| `lapor.php` | UC-06 – PJ melapor digunakan / tidak digunakan (FR-15, 17–20) |
| `admin.php` | UC-07/08/09 – kelola ruangan, fasilitas, jadwal (cek bentrok), pengguna |
| `app.php` | koneksi PDO, sesi, CSRF, logika status ruangan, layout |
| `database/simoka.sql` | skema 6 tabel sesuai ERD + data contoh |

## Aturan status ruangan
- Ada jadwal yang sedang berjalan → **Digunakan**, kecuali PJ melapor “Tidak Digunakan” setelah jadwal dimulai.
- Tidak ada jadwal → **Tersedia**, kecuali PJ melapor “Digunakan” dalam 2 jam terakhir.
- Pencarian untuk waktu mendatang memakai jadwal; jika rentang waktunya mencakup saat ini, laporan PJ ikut diperhitungkan.
