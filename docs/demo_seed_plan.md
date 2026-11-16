# Rencana Seed Demo KPI

Dokumen ini menetapkan data sintetis untuk demo frontend SvelteKit dan Laravel REST API. Seed menunjukkan struktur serta alur akses yang diminta, tetapi tidak mewakili struktur pelaporan resmi atau hasil KPI nyata.

## Prinsip data demo

- Semua identitas, nama unit, target, realisasi, bukti, komentar, tanggal, dan histori dibuat untuk demo.
- Tidak memakai nama pegawai, data transaksi, nomor rekening, dokumen, atau bukti operasional nyata.
- ID dimulai dengan `DEMO-` dan seed dapat dihapus lalu dibuat ulang dengan hasil yang sama.
- Workbook dan HTML hanya dipakai untuk analisis; 96 definisi sumber tidak dimuat ke database demo.
- Seed membuat katalog sintetis berisi enam definisi `DEMO-KPI-*` untuk setiap jabatan yang dinilai. Semuanya `demo_only=true` dan bukan usulan kebijakan final.
- Seed membuat 120 kontrak contoh untuk antrean reviewer. Empat akun self-assignment dibiarkan tanpa kontrak awal: Kasubid pusat, staf pusat, staf pemasaran cabang, dan staf operasional cabang.
- Bobot standar pada KPI terpilih dinormalisasi proporsional ke total 100% saat kontrak dibuat. Ini aturan demo; Admin tetap menjadi pengelola bobot standar katalog.
- Demo lokal dan UAT yang terisolasi boleh memuat seed sintetis. Produksi harus menolak seluruh definisi `demo_only`, siklus, identitas, target, dan realisasi demo.

## Katalog KPI sintetis

Seed membuat 54 definisi placeholder, enam untuk setiap jabatan di bawah. Kode mengikuti pola `DEMO-KPI-{KODE-POSISI}-{01..06}`. Nama indikator berupa `Indikator Demo 01` sampai `Indikator Demo 06`; target dan realisasi seluruhnya sintetis. Distribusi tipe skor hanya untuk memperagakan alur rumus prototype.

| Jabatan demo | Kode posisi | Jumlah definisi |
| --- | --- | ---: |
| Direksi | `DIR` | 6 |
| Kepala Divisi | `KADIV` | 6 |
| Kepala Bidang | `KABID` | 6 |
| Kepala Sub Bidang | `KASUBBID` | 6 |
| Staf pusat | `STAF-PUSAT` | 6 |
| Korwil | `KORWIL` | 6 |
| Kepala Cabang | `KACAB` | 6 |
| Staf Pemasaran | `PEMASARAN` | 6 |
| Staf Operasional | `OPERASIONAL` | 6 |
| **Total** |  | **54** |

Seed menandai semua definisi sebagai `draft=false` di ruang demo agar kontrak dapat merujuknya, dengan `demo_only=true`. Status terbit ini hanya untuk uji tampilan, penyimpanan, penghitungan, dan alur persetujuan; bukan persetujuan kebijakan bank. Deployment produksi menolak semua definisi tersebut.

| Kode akhir | Tipe | Satuan demo | Target demo | Bobot |
| --- | --- | --- | ---: | ---: |
| `01` | A | `unit_demo` | 100 | 20% |
| `02` | A | `item_demo` | 50 | 20% |
| `03` | Q | `%_demo` | 95 | 15% |
| `04` | L | `%_demo` | 10 | 15% |
| `05` | Z | `kejadian_demo` | 0 | 15% |
| `06` | A | `item_demo` | 20 | 15% |

Contoh realisasi dibuat berbeda-beda untuk menampilkan skor rendah, sesuai target, di atas target, tertunda, serta indikator Z yang lolos atau gagal. Semua angka dan satuan ini hanya fixture demo.

## Struktur organisasi sintetis

Untuk data PE fase berikutnya, Korwil Demo 01 dan 04 ditautkan ke Direksi Demo 01, Korwil Demo 02 ke Direksi Demo 02, dan Korwil Demo 03 ke Direksi Demo 03. Tautan ini asumsi demo, bukan bagan pelaporan resmi; alurnya di luar MVP cabang/divisi.

| Bagian | Jumlah | Susunan demo |
| --- | ---: | --- |
| Direksi | 3 | `Direksi Demo 01` sampai `Direksi Demo 03` |
| Divisi | 9 | `Divisi Demo 01` sampai `Divisi Demo 09` |
| Korwil | 4 | `Korwil Demo 01` sampai `Korwil Demo 04` |
| Cabang | 28 | `Cabang Demo 001` sampai `Cabang Demo 028` |
| Bagian cabang | 56 | Satu `Pemasaran Demo` dan satu `Operasional Demo` pada tiap cabang |

Pohon unit demo memakai `Kantor Pusat Demo` sebagai akar, lalu 9 Divisi, masing-masing dengan satu Bidang dan satu Sub Bidang. Direksi termasuk PE, tetapi jalur review-nya ada di fase berikutnya. Tiga Kepala Divisi pertama ditempatkan di bawah Direksi Demo 01, tiga berikutnya di bawah Direksi Demo 02, dan tiga terakhir di bawah Direksi Demo 03 untuk batas data demo. Setiap Sub Bidang memiliki satu Staf Demo; Kepala Sub Bidang dan Staf sama-sama memakai akses pegawai self-only. Reviewer KPI pegawai pusat diarahkan ke Kepala Bidang dan Kepala Divisi jika keduanya tersedia; reviewer aktif yang hanya satu tingkat tetap dapat menyelesaikan tinjauan.

Empat Korwil menjadi unit wilayah dan masing-masing menaungi tujuh cabang hanya untuk demo. Setiap cabang memiliki satu Kepala Cabang Demo dan dua unit bagian, Pemasaran serta Operasional. Seed berikutnya menambahkan satu Kepala Bidang Operasional dan satu Kepala Bidang Pemasaran untuk tiap cabang. Staf Operasional diarahkan ke Supervisor 1 Operasional; staf Pemasaran diarahkan ke Supervisor 1 Pemasaran; kedua supervisor melapor ke Kepala Cabang. Kepala Cabang tetap berada di bawah Korwil pada struktur organisasi. Pembagian Dana, Kredit, dan Remedial pada data staf Pemasaran masih perlu ditentukan untuk contoh KPI.

Pembagian tiga Kepala Divisi per Direksi dan tujuh cabang per Korwil dibuat merata agar alur akses dapat dicoba. Mapping ini bukan data organisasi BKK Jateng dan tidak boleh dipakai saat mengimpor master organisasi sebenarnya. Rancangan role mencakup Admin, Direksi, Supervisor 1, Supervisor 2, Korwil, dan Pegawai self-only. Posisi yang mendapat role Supervisor 1 di cabang belum ada pada seed saat ini.

## Identitas demo

Seed saat ini menyediakan 116 unit, 10 posisi, dan 128 identitas. Setelah pemetaan peran baru diterapkan, target demo tetap 116 unit dengan 12 posisi dan sedikitnya 185 identitas: satu Admin menggantikan satu identitas SDM lama, 56 Kepala Bidang demo ditambahkan untuk fungsi Operasional dan Pemasaran di 28 cabang, serta satu Kepala Sub Bidang cabang sebagai contoh role self-only. Jumlah Kepala Sub Bidang pada organisasi nyata belum ditentukan. Posisi Admin pada seed hanya untuk selector; model produksi sebaiknya memisahkan role aplikasi dari jabatan organisasi.

| Profil demo | Pola ID | Jumlah |
| --- | --- | ---: |
| Admin | `DEMO-ADMIN-001` | 1 |
| PE Direksi, fase berikutnya | `DEMO-DIR-001` sampai `DEMO-DIR-003` | 3 |
| Supervisor 2 divisi (Kepala Divisi) | `DEMO-KADIV-001` sampai `DEMO-KADIV-009` | 9 |
| Supervisor 1 divisi (Kepala Bidang) | `DEMO-KABID-001` sampai `DEMO-KABID-009` | 9 |
| Pegawai self-only: Kepala Sub Bidang | `DEMO-KASUBBID-001` sampai `DEMO-KASUBBID-009` | 9 |
| Pegawai self-only: Kepala Sub Bidang cabang, contoh | `DEMO-KASUBBID-CAB-001` | 1 |
| Pegawai self-only: Staf pusat | `DEMO-STAF-PUSAT-001` sampai `DEMO-STAF-PUSAT-009` | 9 |
| PE Korwil, fase berikutnya | `DEMO-KORWIL-001` sampai `DEMO-KORWIL-004` | 4 |
| Supervisor 2 cabang (Kepala Cabang) | `DEMO-KACAB-001` sampai `DEMO-KACAB-028` | 28 |
| Supervisor 1 cabang: Operasional | `DEMO-SUP1-OPS-001` sampai `DEMO-SUP1-OPS-028` | 28 |
| Supervisor 1 cabang: Pemasaran | `DEMO-SUP1-MKT-001` sampai `DEMO-SUP1-MKT-028` | 28 |
| Pegawai self-only: Pemasaran | `DEMO-PEMASARAN-001` sampai `DEMO-PEMASARAN-028` | 28 |
| Pegawai self-only: Operasional | `DEMO-OPERASIONAL-001` sampai `DEMO-OPERASIONAL-028` | 28 |
| **Minimum setelah penyesuaian seed** |  | **185** |

Setiap identitas menunjuk ke satu pegawai dan satu penugasan demo yang aktif. API mengembalikan daftar ini hanya melalui endpoint demo lokal. Sistem tidak membuat akun email atau password untuk identitas tersebut. Angka 185 adalah minimum rancangan seed, bukan jumlah pada database saat ini; jumlah contoh Kepala Sub Bidang cabang dapat ditambah sesuai susunan demo.

### Daftar user dummy untuk login demo

Pilih salah satu identitas berikut pada selector login demo setelah seed peran baru diterapkan. Daftar ini mencakup peran akses yang diminta dan memakai data sintetis.

Selector menampilkan daftar yang dapat dicari dan memilih user langsung; sistem tidak meminta email atau password. Pola ID target: `DEMO-ADMIN-001`; `DEMO-DIR-001` s.d. `DEMO-DIR-003`; `DEMO-KADIV-001` s.d. `DEMO-KADIV-009`; `DEMO-KABID-001` s.d. `DEMO-KABID-009`; `DEMO-KASUBBID-001` s.d. `DEMO-KASUBBID-009`; `DEMO-KASUBBID-CAB-001`; `DEMO-STAF-PUSAT-001` s.d. `DEMO-STAF-PUSAT-009`; `DEMO-KORWIL-001` s.d. `DEMO-KORWIL-004`; `DEMO-KACAB-001` s.d. `DEMO-KACAB-028`; `DEMO-SUP1-OPS-001` s.d. `DEMO-SUP1-OPS-028`; `DEMO-SUP1-MKT-001` s.d. `DEMO-SUP1-MKT-028`; `DEMO-PEMASARAN-001` s.d. `DEMO-PEMASARAN-028`; dan `DEMO-OPERASIONAL-001` s.d. `DEMO-OPERASIONAL-028`.

Selector hanya tersedia di development lokal; SSO menjadi pekerjaan lanjutan. Seed tidak menyimpan issuer, subject, email, password, client secret, atau token. Data dummy tidak boleh dipakai di produksi.

Nama jabatan `Pimpinan Cabang` pada katalog tidak otomatis disamakan dengan `Kepala Cabang`. Untuk demo, posisi `Kepala Cabang Demo` memakai KPI placeholder sampai pemilik proses mengonfirmasi mapping yang benar.

## Siklus, kontrak, dan bukti sintetis

Seed menyiapkan 120 kontrak contoh dengan reviewer lama. Empat identitas (`DEMO-KASUBBID-001`, `DEMO-STAF-PUSAT-001`, `DEMO-PEMASARAN-001`, dan `DEMO-OPERASIONAL-001`) sengaja tanpa kontrak agar alur self-assignment dapat dicoba. Kontrak baru berstatus DRAFT dan belum dapat diajukan sampai reviewer routing tersedia. Alur PE Korwil/Direksi ditunda. Seed berikutnya perlu mencakup contoh kontrak dengan dua reviewer berurutan dan contoh dengan satu reviewer saja.

- Siklus seed demo adalah satu periode bulanan terbuka dengan ID dinamis `DEMO-CYCLE-YYYYMM`. `starts_at` dan `ends_at` menyimpan rentang tanggal bulan berjalan; tenggat input dan review diisi terpisah. Seeder lokal khusus siklus menutup siklus demo terbuka lain tanpa menghapus kontrak historis.
- Bobot kontrak self-assignment dinormalisasi dari bobot katalog hanya untuk KPI yang dipilih; pemilik mengirim ID KPI dan target, bukan bobot.
- Untuk MVP, buat kontrak demo pada status Draf, Diajukan, Revisi, Terverifikasi, dan Final agar alur utama dapat dicoba. Kontrak hanya merujuk definisi demo yang sudah diterbitkan di ruang demo.
- Sediakan satu kontrak Draf dengan komentar koreksi pegawai, jawaban atasan, dan perubahan target yang memiliki alasan serta audit event.
- Contoh banding dan penilaian perilaku menjadi perluasan seed setelah MVP.
- Gunakan nilai target dan realisasi sintetis dengan unit yang sesuai. Jangan menganggap angka demo sebagai target bank.
- Gunakan referensi seperti `DEMO-EVIDENCE-0001`; tidak ada lampiran atau dokumen operasional nyata.
- MVP hanya mengisi skor KPI demo. Respons API menandai versi formula sebagai draft; skor perilaku, nilai akhir, dan rating tetap kosong sampai kebijakan disetujui.

## Reset dan konsumsi API

- Laravel menyediakan perintah seed/reset untuk demo lokal dan UAT terisolasi. Perintah harus memeriksa environment aktif sebelum menghapus atau membangun ulang data.
- Seed memakai ID tetap agar screenshot, tautan halaman, dan pemanggilan API konsisten setelah reset.
- Pada development lokal, frontend mendapatkan daftar identitas melalui `GET /api/v1/demo/identities`, memilih identitas melalui `POST /api/v1/demo/session`, lalu membaca konteks melalui `GET /api/v1/me`. Ini alur login MVP demo.
- Integrasi SSO dan mapping `sso_identities` ditangani pada tahap lanjutan. Jika seed direset di lingkungan integrasi, mapping yang merujuk ke pegawai demo perlu diterapkan ulang melalui proses terbatas; mapping tidak dimasukkan ke seed bersama.
- Semua operasi kontrak, skor, status, laporan, dan histori tetap diproses oleh Laravel API.
- Endpoint pemilih identitas hanya aktif di local. Route `/api/v1` demo hanya aktif pada local/testing atau staging terisolasi dengan `DEMO_DATA_ALLOWED=true`; produksi menolaknya walau baris demo tertinggal dari pemulihan backup. Perintah reset juga hanya boleh berjalan pada local development atau UAT terisolasi.
