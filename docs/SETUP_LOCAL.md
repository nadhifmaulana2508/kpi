# Setup Local dan Pindah Laptop

Panduan ini adalah checklist resmi untuk menjalankan prototype KPI Perbankan di laptop baru. Prototype saat ini memakai Laravel REST API, SvelteKit, data dummy, dan SQLite untuk development lokal.

## 1. Yang perlu dipindahkan dari laptop lama

Source code yang wajib ikut:

- `backend/`
- `frontend/`
- `docs/`
- `PRD.md`
- `PRD_KPI_Perbankan.md`
- `status.md`
- `README.md`

File lokal yang jangan di-commit ke repository:

- `backend/.env`
- `frontend/.env.local`
- `backend/database/database.sqlite`
- `backend/vendor/`
- `frontend/node_modules/`

`.env` dapat berisi `APP_KEY` dan konfigurasi lokal. Jika database SQLite ingin dipertahankan persis, salin `backend/database/database.sqlite` secara terpisah melalui media atau storage pribadi yang aman.

## 2. Commit dan push dari laptop lama

Jalankan dari root project:

```powershell
Set-Location D:\Framwork_Laravel\kpi-perbankan
git status
git add frontend/src/routes/+page.svelte frontend/src/routes/page.css status.md docs README.md
git commit -m "chore: checkpoint ui ux step 27"
git push origin main
```

Jika branch utama bukan `main`, gunakan nama branch yang dipakai repository.

## 3. Prasyarat laptop baru

Install:

- Git
- Laravel Herd dengan PHP 8.3 atau lebih baru
- Composer
- Node.js LTS dan npm
- SQLite/PDO SQLite aktif pada PHP Herd

MySQL belum diperlukan untuk prototype UI/UX. Migrasi ke MySQL akan dibuat sebagai langkah backend terpisah sebelum UAT atau integrasi SSO.

## 4. Clone repository

```powershell
git clone <URL_REPOSITORY> D:\Framwork_Laravel\kpi-perbankan
Set-Location D:\Framwork_Laravel\kpi-perbankan
```

## 5. Siapkan backend Laravel

Dengan PHP dan Composer Herd yang sudah tersedia di PATH:

```powershell
Set-Location D:\Framwork_Laravel\kpi-perbankan\backend
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+$' -Quiet)) { php artisan key:generate }
if (-not (Test-Path database\database.sqlite)) { New-Item database\database.sqlite -ItemType File | Out-Null }
php artisan migrate
php artisan db:seed
```

`db:seed` membangun ulang data sintetis untuk local demo. Jangan menjalankannya pada database produksi atau database UAT yang berisi data penting.

Jika ingin memakai binary PHP Herd secara eksplisit, gunakan pola berikut:

```powershell
$Php = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$Composer = Join-Path $env:USERPROFILE '.config\herd\bin\composer.phar'
Set-Location D:\Framwork_Laravel\kpi-perbankan\backend
& $Php $Composer install
& $Php artisan key:generate
& $Php artisan migrate
& $Php artisan db:seed
```

Jalankan API pada terminal backend:

```powershell
php artisan serve --host=127.0.0.1 --port=8100
```

API demo tersedia di `http://127.0.0.1:8100`.

## 6. Siapkan frontend SvelteKit

Buka terminal baru:

```powershell
Set-Location D:\Framwork_Laravel\kpi-perbankan\frontend
if (-not (Test-Path .env.local)) { Copy-Item .env.example .env.local }
npm.cmd install
npm.cmd run dev -- --host 127.0.0.1 --port 5180
```

Pastikan `frontend/.env.local` memuat konfigurasi proxy berikut:

```env
VITE_API_PROXY_TARGET=http://127.0.0.1:8100
```

Frontend demo tersedia di [http://127.0.0.1:5180](http://127.0.0.1:5180).

Gunakan `npm.cmd`, bukan `npm`, jika PowerShell laptop baru memblokir execution policy untuk `npm.ps1`.

## 7. Verifikasi instalasi

Dari folder `frontend`:

```powershell
npm.cmd run check
npm.cmd run build
curl.exe -I http://127.0.0.1:5180
```

Verifikasi API dari terminal lain:

```powershell
curl.exe -I http://127.0.0.1:8100/up
```

Login prototype masih memakai selector user dummy. SSO belum diaktifkan.

## 8. Troubleshooting singkat

- `500` saat login: cek backend aktif di port `8100`, lalu cek `VITE_API_PROXY_TARGET`.
- Data dummy kosong: jalankan `php artisan db:seed` dari folder `backend`.
- Port bentrok: gunakan port lain dan samakan `VITE_API_PROXY_TARGET`, `FRONTEND_ORIGINS`, serta `SANCTUM_STATEFUL_DOMAINS`.
- `npm` diblokir PowerShell: gunakan `npm.cmd`.
- Perubahan CRUD mapping Step 27 hilang setelah refresh: itu memang frontend-only dan belum disimpan ke backend.

## 9. Titik lanjut project

Setelah setup selesai, baca [status.md](../status.md). Status terakhir mencatat Step 27 selesai dan rekomendasi berikutnya adalah Step 28: preview dampak perubahan mapping KPI per jabatan.

Saat melanjutkan pekerjaan, gunakan urutan referensi berikut:

1. [PRD.md](../PRD.md)
2. [status.md](../status.md)
3. file source pada `frontend/src/routes/`
4. [README.md](../README.md)

## 10. Migrasi MySQL nanti

Untuk fase backend serius, buat database MySQL lalu ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kpi_perbankan
DB_USERNAME=root
DB_PASSWORD=
```

Setelah koneksi diverifikasi, jalankan migration dan seed pada database development yang benar. Jangan melakukan migrasi dengan database produksi tanpa backup dan persetujuan pemilik environment.
