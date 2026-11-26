# Data Simulasi Self-Assignment KPI

`FocusedDemoSeeder` menyiapkan satu struktur kecil agar alur staf dapat dicoba tanpa memenuhi aplikasi dengan banyak kontrak contoh. Semua nama, indikator, target, dan periode adalah data simulasi; indikator ini belum menjadi kebijakan resmi bank.

## Isi seed lokal

- 7 unit: Kantor Pusat, Divisi Operasional, Bidang Layanan, Sub Bidang Administrasi, Wilayah Jakarta, Cabang Jakarta Pusat, dan Operasional Cabang.
- 9 jabatan dan 9 identitas, termasuk Admin KPI, staf Pusat, Kepala Sub Bidang, Kepala Wilayah, Kepala Cabang, dan staf Operasional Cabang.
- 15 indikator contoh untuk lima jabatan self-service.
- 1 siklus bulanan terbuka dan tanpa kontrak awal.

Seed ulang hanya boleh dijalankan pada database lokal atau UAT terisolasi. Perintah ini menghapus dan membuat ulang seluruh baris berlabel simulasi.

## Identitas untuk login lokal

| Nama | ID | Akses utama |
| --- | --- | --- |
| Admin KPI | `DEMO-SDM-001` | Pengaturan Admin |
| Kepala Sub Bidang Administrasi | `DEMO-KASUBBID-001` | Self-assignment dan riwayat sendiri |
| Staf Operasional Pusat | `DEMO-STAF-PUSAT-001` | Self-assignment dan riwayat sendiri |
| Kepala Wilayah Jakarta | `DEMO-KORWIL-001` | Self-assignment dan riwayat sendiri |
| Kepala Cabang Jakarta Pusat | `DEMO-KACAB-001` | Self-assignment dan riwayat sendiri |
| Staf Operasional Cabang | `DEMO-OPERASIONAL-001` | Self-assignment dan riwayat sendiri |

Direktur dan Kepala Bidang disediakan sebagai atasan dalam struktur organisasi. Pemilih identitas lokal dapat dicari berdasarkan nama, jabatan, unit, atau ID.

## Batas alur saat ini

- Staf, Kepala Sub Bidang, Kepala Cabang, dan Korwil mendapat dua menu: **Susun KPI** dan **Riwayat saya**.
- Daftar riwayat diambil dari kontrak milik identitas yang masuk; kontrak reviewer atau pegawai lain tidak ditampilkan.
- Simpan membuat kontrak berstatus `DRAFT` di backend. Setelah tersimpan, data muncul di riwayat dan tidak dapat disunting melalui alur ini.
- Pengisian realisasi, bukti, pengajuan ke reviewer, dan persetujuan belum menjadi bagian fokus ini.
- Penetapan indikator resmi per jabatan masih menunggu konfigurasi Admin. KPI dan target seed hanya untuk mencoba UI serta simpan data.

Selector identitas hanya tersedia di lingkungan lokal. Produksi menolak seluruh route dan baris yang hanya untuk simulasi.
