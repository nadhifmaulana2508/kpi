# KPI Perbankan

Prototype MVP untuk alur penilaian KPI kantor pusat, Korwil, dan cabang. Backend memakai Laravel REST API; frontend terpisah memakai SvelteKit dan shadcn-svelte. Seluruh isi seed adalah data sintetis.

## Kebutuhan lokal

- PHP 8.3 atau lebih baru dan Composer
- Node.js dan npm
- SQLite

Backend `.env.example` memakai `APP_ENV=local` dan SQLite. Seed demo boleh dijalankan di local. Seed di `APP_ENV=staging` hanya diizinkan untuk UAT terisolasi dengan `DEMO_DATA_ALLOWED=true`. Pemilih user dummy tersedia pada `APP_ENV=local`; MVP demo tidak membutuhkan SSO.

Seed ulang membangun ulang seluruh data demo dan hanya boleh dijalankan pada database lokal atau UAT terisolasi.

## Menjalankan backend

Dari root repository, di PowerShell:

```powershell
$Php = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$Composer = Join-Path $env:USERPROFILE '.config\herd\bin\composer.phar'
Set-Location backend
& $Php $Composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+$' -Quiet)) { & $Php artisan key:generate }
& $Php artisan migrate
& $Php artisan db:seed
& $Php artisan serve --host=127.0.0.1 --port=8100
```

Contoh di atas memakai PHP 8.4 dari Herd. Untuk instalasi lain, arahkan PHP dan Composer ke runtime PHP 8.3 atau lebih baru yang sama.

`php artisan db:seed` menjalankan `DemoSeeder` dan membangun ulang data berlabel demo. Perintah ini menghapus lalu membuat ulang data demo, jadi gunakan hanya pada database lokal atau UAT yang terisolasi. Jangan jalankan seed demo di produksi.

Route `/api/v1` pada prototype juga menolak semua request di produksi. Route aktif hanya untuk local/testing atau staging dengan `DEMO_DATA_ALLOWED=true`.

## Menjalankan frontend

Buka terminal kedua:

```powershell
Set-Location frontend
npm install
npm run dev
```

Buka alamat yang dicetak Vite. Pada workspace ini frontend KPI berjalan di `http://127.0.0.1:5180` supaya tidak bentrok dengan project lain. Vite meneruskan request `/api` dan `/sanctum` ke Laravel KPI di port 8100 melalui `VITE_API_PROXY_TARGET`.

Salin `frontend/.env.example` menjadi `frontend/.env.local` jika perlu mengarahkan browser langsung ke host Laravel terpisah. Isi `VITE_API_BASE_URL` dengan origin API dan tambahkan origin FE yang tepat ke `FRONTEND_ORIGINS` backend. CORS Laravel sudah mengizinkan kredensial untuk allowlist tersebut; sesi Sanctum juga memerlukan `SANCTUM_STATEFUL_DOMAINS` dan `SESSION_DOMAIN` yang cocok. Jika frontend dan API tidak berbagi top-level domain, sepakati mekanisme autentikasi lain sebelum UAT.

Login pada prototype lokal memakai selector identitas sintetis. [Daftar ID dan peran target](docs/demo_seed_plan.md#daftar-user-dummy-untuk-login-demo) mencatat perubahan seed yang masih perlu diterapkan. Integrasi SSO dikerjakan setelah demo ketika TI menyediakan protokol, metadata, domain, claim mapping, dan akun uji.

## Referensi

- [Panduan setup local dan pindah laptop](docs/SETUP_LOCAL.md)
- [PRD dan papan progres](PRD_KPI_Perbankan.md)
- [Kontrak OpenAPI](docs/openapi.yaml)
- [Contoh request API](docs/api_examples.md)
- [Rencana seed demo](docs/demo_seed_plan.md)

Panduan ini hanya untuk demo lokal dengan data sintetis. Aturan KPI, mapping reviewer, sumber master data resmi, dan integrasi SSO perlu disahkan sebelum UAT berbasis data/akun nyata.
