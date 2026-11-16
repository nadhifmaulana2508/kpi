# PRD Sistem KPI Perbankan

Dokumen kerja ini merangkum kebutuhan awal sistem KPI untuk kantor pusat, Korwil, dan cabang. Tujuannya adalah mengubah katalog dan alur prototipe yang sudah ada menjadi aplikasi dengan struktur organisasi, akses, target, bukti, dan proses penilaian yang dapat dikelola. Status dokumen: draft untuk validasi bisnis. Target demo MVP: lima hari kerja setelah alur dan akses lokal siap.

## 1. Status dan batasan rancangan

| Item | Status |
| --- | --- |
| Struktur organisasi | Mengikuti informasi awal pengguna. Garis pelaporan rinci belum dikonfirmasi. |
| Katalog indikator | Sumber yang diberikan berisi 96 indikator untuk 16 jabatan. Isinya dianalisis sebagai referensi dan tidak dimuat ke seed demo. |
| Rumus dan bobot | Usulan dalam prototipe, belum menjadi kebijakan yang disahkan. |
| Periode penilaian | Siklus bulanan disepakati. Setiap siklus memakai tanggal mulai dan akhir yang dapat diatur; tenggat input dan review belum ditetapkan. |
| Data awal | Seluruh data bisnis pada demo dan UAT, termasuk definisi KPI, unit, pegawai, siklus, target, realisasi, bukti, dan histori, dibuat sintetis. Tidak mengimpor master SDM atau transaksi nyata. |
| Teknologi | Laravel REST API dan frontend terpisah memakai SvelteKit serta shadcn-svelte. |
| Adapter frontend | Usulan default `adapter-node` untuk deployment Node mandiri/container. Host dan persyaratan TI tetap perlu dipastikan sebelum deploy. |
| Peran | Fokus awal hanya divisi dan cabang. Admin mengelola katalog; Supervisor 1/2 mengikuti jabatan di dua area itu; Kepala Divisi dan Kepala Cabang adalah PE. Direksi dan Korwil juga PE, tetapi alurnya di luar tahap awal. |
| Login MVP | Selector berisi user dummy untuk demo lokal. Akun, password, dan identitas pegawai nyata tidak dipakai. |
| SSO | Di luar MVP demo. Fondasi pemetaan identitas sudah ada; callback dan integrasi IdP dikerjakan setelah protokol serta akses TI tersedia. |
| Cakupan MVP | API REST Laravel, frontend SvelteKit + shadcn-svelte, data bisnis sintetis, selector user dummy, dan alur penilaian demo. Seluruh data demo ditolak di produksi. |

Target satu minggu adalah durasi pengerjaan demo. Siklus penilaian yang dipilih adalah bulanan dengan rentang tanggal yang ditetapkan per siklus.

## 2. Konteks organisasi

Struktur yang perlu didukung:

- Kantor pusat dengan 3 direksi.
- 9 divisi. Jabatan dapat mencakup Kepala Divisi, Kepala Bidang, Kepala Sub Bidang, dan Staf.
- 4 Korwil.
- 28 cabang, masing-masing dipimpin Kepala Cabang.
- Bagian cabang sekurangnya mencakup Pemasaran dan Operasional.

Aplikasi tidak boleh mengunci jumlah atau susunan jabatan di dalam kode. Struktur unit dan penugasan harus memiliki masa berlaku agar perubahan organisasi tidak mengubah histori penilaian. Admin menetapkan katalog KPI. Pengelolaan master organisasi oleh Admin masih perlu dipisahkan dari tugas pengelolaan katalog.

Workbook dan prototipe yang diberikan memuat kelompok Bisnis, Operasional, serta Pendukung dan Kontrol dengan 16 jabatan. Keduanya menjadi bahan analisis katalog, bukan isi database demo. Jabatan pada katalog sumber belum mencakup seluruh jabatan pimpinan pusat dan jenjang divisi yang disebutkan di atas.

### Cakupan jabatan pada katalog saat ini

Semua jabatan berikut memiliki enam indikator dalam katalog:

| Kelompok | Jabatan yang tercakup |
| --- | --- |
| Bisnis | AO Kredit, Petugas Remedial, AO Dana, Pimpinan Cabang |
| Operasional | Analis Kredit, Staf Administrasi Kredit, Teller, Customer Service, Kepala Operasional Cabang |
| Pendukung dan Kontrol | Staf Akuntansi dan Pelaporan, Staf Teknologi Informasi, Staf SDM, Staf Kepatuhan dan APU PPT, Staf Manajemen Risiko, Auditor Internal, Staf Umum dan Pengadaan |

Katalog belum mencantumkan jabatan Direksi, Kepala Divisi, Kepala Bidang, Kepala Sub Bidang, Korwil, atau pimpinan bagian Pemasaran cabang. Nama “Pimpinan Cabang” perlu dipastikan apakah dipetakan ke jabatan Kepala Cabang. Katalog adalah templat jabatan, bukan daftar jumlah pegawai atau penugasan aktual.

## 3. Tujuan produk

Sistem membantu bank untuk:

1. Menetapkan target KPI yang terkait dengan unit, jabatan, pegawai, dan periode yang benar.
2. Mencatat realisasi beserta sumber dan bukti yang dapat ditinjau.
3. Mengarahkan pemeriksaan kepada atasan yang berwenang.
4. Menghasilkan nilai dan status yang dapat ditelusuri sampai ke indikator dan bukti.
5. Menampilkan capaian berdasarkan hak akses dan cakupan organisasi.
6. Menyimpan histori perubahan target, penilaian, keputusan, dan banding.

## 4. Pengguna dan kewenangan

| Pengguna | Cakupan | Tindakan yang diizinkan |
| --- | --- | --- |
| Admin | Katalog KPI | Menetapkan dan mengelola katalog KPI. Pada demo, isi katalog tetap sintetis; akses untuk mengubah kontrak atau hasil tidak otomatis diberikan. |
| Supervisor 1 | Divisi atau bagian cabang yang ditetapkan | Di divisi: Kepala Bidang. Di cabang: Kepala Bidang Operasional untuk backoffice dan Kepala Bidang Pemasaran untuk staf Dana, Kredit, dan Remedial. Dapat mengisi KPI diri sendiri dan meninjau bawahan. |
| PE / Supervisor 2 | Divisi atau cabang yang ditetapkan | Di tahap awal: Kepala Divisi dan Kepala Cabang menjadi PE tingkat Supervisor 2. Mereka meninjau tahap berikutnya dan dapat mengisi KPI diri sendiri. |
| Pegawai (self-only) | Penugasan sendiri | Staf serta Kepala Sub Bidang di divisi maupun cabang memilih KPI yang tersedia untuk jabatannya, mengusulkan target, mengisi, dan mengajukan KPI diri sendiri; tidak membuat atau meninjau kontrak orang lain. |
| PE di luar tahap awal | Korwil dan Direksi | Keduanya adalah PE. Alur penilaian, cakupan, dan urutan review mereka belum masuk fokus cabang/divisi saat ini. |

Backend menghitung hak akses dari peran aplikasi, penugasan yang berlaku, unit turunan, dan reviewer yang tercatat. Jika Supervisor 1 dan PE/Supervisor 2 sama-sama tersedia, kontrak melewati keduanya secara berurutan. Jika hanya satu yang tersedia, satu reviewer itu dapat menyelesaikan tinjauan sampai final. Submit ditolak jika tidak ada reviewer aktif. Peran PE Direksi dan Korwil tercatat, tetapi alur mereka tidak masuk tahap ini. Kontrak menyimpan snapshot pegawai, jabatan, unit, dan reviewer saat dibuat agar perpindahan pegawai tidak mengubah hasil historis.

Kontrak tidak dapat diajukan bila tidak memiliki jalur tinjauan aktif yang sah. Penugasan pejabat sementara dan delegasi harus memiliki tanggal mulai/akhir serta alasan. Data demo boleh tetap memuat Direksi dan Korwil sebagai PE, tetapi kriteria penerimaan tahap ini hanya menguji jalur divisi dan cabang.

## 5. Alur kerja

1. **Siapkan master data demo.** Sistem mengisi organisasi, jabatan, pegawai, atasan, dan penugasan Korwil dari seed dummy yang dapat direset. Impor data nyata disiapkan setelah MVP.
2. **Siapkan periode.** Admin atau pengelola siklus membuat periode bulanan dengan tanggal mulai dan akhir, tenggat isian, dan tahapan persetujuan. Rentang tanggal disimpan per siklus, tidak diturunkan dari nama bulan.
3. **Kelola katalog.** Admin menetapkan definisi indikator, satuan, rumus, arah skor, sumber data, bobot standar, target, dan aturan bukti. Isi demo seluruhnya sintetis; katalog resmi dapat dimasukkan Admin setelah disahkan.
4. **Pilih KPI untuk diri sendiri.** Pegawai self-only memilih indikator dari katalog yang Admin sediakan untuk jabatannya dan mengusulkan target pada kontrak bulan berjalan. Bobot standar katalog dibagi proporsional di antara KPI yang dipilih agar totalnya 100%. Supervisor yang berwenang dapat mengoreksi target/bobot dengan alasan.
5. **Isi realisasi.** Pegawai memasukkan realisasi dan bukti untuk KPI yang dipilihnya. Sistem menyimpan draf dan menunjukkan kelengkapan.
6. **Tinjau dan revisi.** Jika kedua tingkat tersedia, Supervisor 1 memeriksa nilai, bukti, dan catatan terlebih dahulu, lalu PE/Supervisor 2 melakukan tinjauan akhir. Jika hanya satu tingkat tersedia, reviewer tersebut menangani tinjauan dan finalisasi.
7. **Finalisasi.** Reviewer terakhir yang tersedia mengunci hasil. Sistem menolak pengajuan tanpa reviewer aktif.
8. **Banding.** Pegawai dapat mengajukan alasan dan bukti dalam tenggat yang ditentukan. Keputusan banding dicatat tanpa menghapus hasil sebelumnya.
9. **Pantau dan laporkan.** Pimpinan melihat jumlah/status kontrak dan daftar yang memerlukan tindakan sesuai cakupan aksesnya. Agregasi skor per unit menyusul setelah aturan tiap indikator disepakati.

### Status dan transisi penilaian

| Status saat ini | Tindakan | Aktor | Status berikutnya |
| --- | --- | --- | --- |
| Draf | Kirim untuk ditinjau | Pegawai pemilik | Diajukan |
| Revisi | Perbaiki lalu kirim ulang | Pegawai pemilik | Diajukan |
| Diajukan | Verifikasi atau minta revisi | Supervisor 1 jika ada; jika tidak, satu-satunya PE/Supervisor 2 | Terverifikasi bila ada dua reviewer; Final bila hanya satu; atau Revisi |
| Terverifikasi | Tinjauan akhir atau minta revisi | PE/Supervisor 2 | Final atau Revisi |
| Final | Ajukan banding dalam tenggat | Pegawai | Banding terbuka; tahap setelah MVP |
| Banding terbuka | Putuskan banding | Peran peninjau banding belum ditetapkan | Banding diterima atau ditolak |

Kontrak dikunci setelah diajukan. Perubahan setelah status Final hanya dilakukan melalui proses banding atau pembukaan kembali yang disetujui dan tercatat. Setelah banding diterima, kontrak kembali ke status Revisi dan harus melewati verifikasi serta finalisasi lagi.

## 6. Kebutuhan fungsional

| ID | Kebutuhan | Prioritas MVP |
| --- | --- | --- |
| FR-01 | Sajikan pohon unit sintetis sesuai struktur demo. Pengelolaan unit dan masa berlaku melalui UI/API masuk tahap berikutnya | Wajib: data seed saja |
| FR-02 | Gunakan pegawai, jabatan, atasan, dan penugasan sintetis untuk hak akses serta snapshot kontrak. CRUD master dan histori penugasan masuk tahap berikutnya | Wajib: data seed saja |
| FR-03 | Impor master organisasi dan pegawai dari format yang disepakati, dengan laporan kesalahan per baris | Setelah MVP |
| FR-04 | Sediakan selector identitas sintetis untuk memilih peran demo; integrasi login SSO disiapkan sebagai tahap lanjutan | Wajib: selector user dummy lokal |
| FR-05 | Sajikan katalog KPI sintetis dan siapkan pengelolaan katalog oleh Admin | Wajib: katalog seed; editor Admin menyusul setelah alur akses disepakati |
| FR-06 | Sajikan satu siklus demo yang sudah disiapkan. Pengaturan siklus dan tenggat lewat UI masuk tahap berikutnya | Wajib: satu siklus seed |
| FR-07 | Izinkan pegawai self-only memilih KPI dari katalog yang berlaku untuk jabatannya dan mengusulkan target pada kontrak sendiri; bobot pilihan dinormalisasi ke 100%, perubahan supervisor beralasan dan tercatat | Wajib |
| FR-08 | Masukkan realisasi, catatan, status kelengkapan, serta referensi bukti sintetis. Unggah file masuk tahap berikutnya | Wajib |
| FR-09 | Tampilkan kemajuan pengisian dan alasan indikator yang belum siap dihitung | Wajib |
| FR-10 | Dukung pengajuan pegawai, tinjauan berurutan oleh kedua supervisor jika keduanya tersedia, atau satu reviewer sampai final jika hanya satu tersedia | Wajib |
| FR-11 | Hitung skor KPI demo di backend menggunakan versi formula yang ditandai `demo_only`; nilai akhir dan rating tetap kosong sampai aturan lengkap disahkan | Wajib untuk demo |
| FR-12 | Tampilkan dashboard personal dan daftar tinjauan atasan sesuai cakupan akses | Wajib |
| FR-13 | Ekspor daftar dan ringkasan ke CSV | Setelah MVP |
| FR-14 | Audit perubahan target, realisasi, submit, tinjauan, finalisasi, dan perubahan hak akses | Wajib untuk tindakan inti |
| FR-15 | Sediakan pengingat tenggat melalui kanal yang disetujui TI | Setelah MVP |
| FR-16 | Integrasikan realisasi dari core banking, SIMPEG, MIS, atau sumber lain | Setelah MVP |
| FR-17 | Sediakan REST API JSON berversi di bawah `/api/v1` untuk alur vertikal MVP | Wajib |
| FR-18 | SvelteKit mengambil dan mengubah data melalui API. Frontend tidak mengakses database atau menjadi sumber aturan bisnis | Wajib |
| FR-19 | Sediakan kontrak API melalui `docs/openapi.yaml` untuk endpoint MVP dan rencana endpoint berikutnya | Wajib |
| FR-20 | Batasi selector identitas demo ke development lokal; siapkan integrasi SSO sebagai tahap lanjutan | Wajib untuk demo; SSO setelah MVP |
| FR-21 | Simpan seed, perubahan, dan histori alur pada backend. Frontend tidak memakai `localStorage` sebagai sumber data utama | Wajib |
| FR-22 | Simpan lima aspek penilaian perilaku, penilai, catatan, dan referensi bukti pada kontrak | Setelah MVP |
| FR-23 | Catat komentar koreksi sebelum kontrak diajukan; perubahan target/bobot oleh atasan harus memiliki alasan dan audit | Wajib |

### Kontrak REST API awal

Backend mengembalikan JSON dengan ID stabil, validasi, kode status HTTP, dan pesan kesalahan yang konsisten. Daftar yang dapat bertambah harus mendukung pagination dan filter yang terdokumentasi. Perubahan kontrak yang tidak kompatibel menggunakan versi API baru.

Daftar ini mencakup produk bertahap. MVP satu minggu hanya mengimplementasikan endpoint untuk alur yang ditetapkan pada Bagian 10. Endpoint master data, konfigurasi katalog, banding, ekspor, dan fitur lain masuk tahap lanjutan.

| Metode dan endpoint | Tujuan |
| --- | --- |
| `GET /api/v1/demo/identities` | Mengambil daftar identitas sintetis yang tersedia di lingkungan lokal |
| `POST /api/v1/demo/session` | Memilih salah satu identitas sintetis yang tersedia di lingkungan lokal |
| `GET /api/v1/me` | Mengembalikan identitas aktif dan cakupan akses dari sesi user demo; sesi SSO ditambahkan pada tahap integrasi |
| `GET /api/v1/dashboard` | Mengambil ringkasan sesuai identitas dan cakupan |
| `GET /api/v1/org-units` | Mengambil pohon unit organisasi demo |
| `POST /api/v1/org-units` dan `PATCH /api/v1/org-units/{id}` | Admin mengelola unit demo dengan tanggal efektif dan histori; editor master bukan bagian alur awal |
| `GET /api/v1/positions` dan `GET /api/v1/positions/{id}` | Mengambil daftar atau detail jabatan demo |
| `POST /api/v1/positions` dan `PATCH /api/v1/positions/{id}` | Admin menambah, mengubah, atau menonaktifkan jabatan tanpa menghapus referensi historis |
| `GET /api/v1/employees` dan `GET /api/v1/employees/{id}` | Mencari atau melihat pegawai dalam cakupan akses |
| `POST /api/v1/employees` dan `PATCH /api/v1/employees/{id}` | Admin membuat atau mengubah pegawai sintetis |
| `GET /api/v1/employees/{id}/assignments` | Melihat riwayat penugasan pegawai |
| `POST /api/v1/employees/{id}/assignments` dan `PATCH /api/v1/employees/{id}/assignments/{assignmentId}` | Admin membuat penugasan atau menutup penugasan lama tanpa menghapus histori |
| `GET /api/v1/kpi-definitions` | Mengambil katalog indikator dan versinya |
| `GET /api/v1/kpi-definitions/{id}` | Melihat definisi lengkap dan status versinya |
| `POST /api/v1/kpi-definitions` dan `PATCH /api/v1/kpi-definitions/{id}` | Admin membuat dan mengubah versi KPI draft |
| `POST /api/v1/kpi-definitions/{id}/versions` dan `POST /api/v1/kpi-definitions/{id}/publish` | Admin membuat versi baru dan menerbitkan katalog demo; pengesahan katalog resmi tetap mengikuti proses bank |
| `GET /api/v1/cycles` | Mengambil siklus penilaian demo |
| `POST /api/v1/cycles` dan `PATCH /api/v1/cycles/{id}` | Admin membuat siklus bulanan dengan tanggal mulai/akhir dan mengubah pengaturan sebelum dibuka |
| `POST /api/v1/cycles/{id}/open`, `POST /api/v1/cycles/{id}/close`, `POST /api/v1/cycles/{id}/finalize` | Admin mengatur transisi siklus; backend memeriksa kelengkapan dan mencatat alasan |
| `GET /api/v1/contracts` | Pegawai melihat kontrak sendiri; supervisor cabang/divisi melihat kontrak dalam jalur review; PE Direksi/Korwil berada di luar cakupan MVP awal; Admin tidak otomatis mendapat semua hasil kontrak |
| `GET /api/v1/contracts/{id}` | Pemilik dan supervisor yang ditugaskan melihat detail kontrak; akses PE Direksi/Korwil akan dirancang pada fase berikutnya; role Admin katalog tidak membuka akses hasil otomatis |
| `POST /api/v1/contracts` | Pegawai self-only membuat kontrak untuk dirinya dengan memilih KPI yang tersedia bagi jabatannya; kontrak unit di luar MVP |
| `PATCH /api/v1/contracts/{id}/items/{itemId}` | Memperbarui realisasi oleh pegawai atau target/bobot oleh atasan berwenang saat kontrak Draf/Revisi |
| `POST /api/v1/contracts/{id}/comments` | Mencatat komentar atau usulan koreksi dari pegawai atau supervisor yang ditugaskan sebelum kontrak diajukan |
| `PUT /api/v1/contracts/{id}/behavior` | Endpoint rencana; penilai, kewenangan, dan bobot perilaku belum ditetapkan |
| `POST /api/v1/contracts/{id}/submit` | Mengajukan kontrak untuk ditinjau |
| `POST /api/v1/contracts/{id}/reviews` | Menyimpan tindakan Supervisor 1/2 sesuai tahap yang telah disahkan |
| `POST /api/v1/contracts/{id}/appeals` | Mengajukan banding atas kontrak final |
| `POST /api/v1/appeals/{id}/decision` | Menerima atau menolak banding dengan alasan |
| `GET /api/v1/reports/summary` | Mengambil ringkasan sesuai cakupan identitas aktif |
| `GET /api/v1/reports/export` | Mengunduh rekap CSV demo |
| `GET /api/v1/audit-events` | Setiap role melihat audit sesuai kewenangannya; hak Admin atas audit perubahan katalog tidak memperluas akses ke detail hasil KPI |

Endpoint pemilih identitas demo hanya tersedia pada lingkungan demo lokal dan tidak boleh diaktifkan di produksi. SSO bukan syarat penerimaan MVP demo; callback IdP menjadi pekerjaan lanjutan setelah TI menentukan protokol dan konfigurasi. Endpoint konfigurasi demo hanya dapat dipakai Admin yang sudah terautentikasi. Backend tetap menjadi pemilik otorisasi, validasi status, dan perhitungan skor; pembatasan akses tidak bergantung pada menu yang ditampilkan frontend.

## 7. Model data inti

| Entitas | Data utama | Hubungan dan aturan |
| --- | --- | --- |
| Pegawai | ID pegawai, status kerja, identitas dasar yang diperlukan | Dapat memiliki satu atau lebih penugasan sesuai kebijakan SDM. |
| Identitas demo | ID demo, ID pegawai, cakupan/peran demo | Hanya aktif di lingkungan lokal/pengembangan. Tidak memakai email, password, atau identitas orang nyata. |
| Identitas SSO | Penyedia, identifier pengguna yang stabil dari IdP, ID pegawai | Identifier dipetakan ke pegawai. Jangan memakai alamat email sebagai satu-satunya kunci permanen. |
| Unit organisasi | ID unit, kode, nama, tipe, unit induk, tanggal berlaku | Membentuk pohon pusat, divisi, bidang, sub bidang, Korwil, cabang, dan bagian. |
| Jabatan | ID jabatan, nama, rumpun, level | Definisi jabatan terpisah dari unit organisasi. |
| Penugasan | Pegawai, jabatan, unit, atasan, tanggal mulai/akhir | Snapshot penugasan yang dipakai kontrak tetap tersimpan meski pegawai pindah. |
| Periode KPI | Jenis, tanggal mulai/akhir, tenggat, status, penanda demo | Periode dapat dibuat tanpa mengubah periode yang sudah final. Siklus sintetis ditolak di produksi. Jenis periode menunggu keputusan bisnis. |
| Definisi KPI | Kode, nama, definisi, tipe skor, satuan, rumus, sumber, aturan bukti | Setiap revisi definisi memiliki versi dan tanggal berlaku. Kontrak lama tetap menunjuk versi yang digunakan saat disetujui. |
| Kontrak KPI | Subjek pegawai atau unit, periode, penugasan snapshot, status | Kontrak unit dipisahkan dari kontrak individu agar capaian organisasi dapat ditelusuri dan diagregasi dengan aturan yang disetujui. Komentar koreksi disimpan sebagai catatan append-only. |
| Item kontrak | Versi definisi KPI, target, bobot, realisasi, skor | Satu kontrak memiliki beberapa item. Bobot dan target yang disetujui disimpan pada kontrak, tidak dibaca ulang dari katalog terbaru. |
| Penilaian perilaku | Lima nilai aspek, penilai, komentar, referensi bukti, waktu | Pada demo diisi SDM. Penilai sebenarnya dan penggunaan bobot perilaku perlu disahkan. |
| Bukti | File atau referensi, metadata, pemilik, tanggal | Terhubung ke item kontrak. Hak lihat mengikuti hak akses pada kontrak. |
| Tinjauan dan banding | Aktor, keputusan, catatan, alasan, waktu | Setiap tindakan menjadi catatan baru agar urutan dan keputusan sebelumnya tetap dapat dilihat. |
| Audit event | Aktor, tindakan, objek, perubahan, waktu, sumber | Mencatat perubahan master, target, bobot, realisasi, keputusan, akses, dan ekspor. |

Satu periode dapat memiliki kontrak untuk unit dan pegawai. Kontrak bawahan dapat ditautkan ke target unit induk, tetapi aplikasi tidak otomatis menyalin target ke bawah. Atasan menetapkan turunan target dengan aturan yang disetujui. Setiap kontrak menyimpan snapshot penugasan saat kontrak disahkan agar perpindahan pegawai tidak mengubah laporan periode lama.

## 8. Aturan penilaian dari prototipe

Aturan berikut merinci perilaku kode HTML saat ini. Semuanya tetap berstatus usulan sampai pemilik proses menyetujuinya.

### Skor indikator

| Tipe | Rumus dalam prototipe | Validasi dan batas skor |
| --- | --- | --- |
| A, lebih tinggi lebih baik | `100 × realisasi ÷ target` | Target harus lebih dari 0. Skor dibatasi 0 sampai 120. |
| Q, rasio mutu atau penyelesaian | `100 × realisasi ÷ target` | Target lebih dari 0. Target dan realisasi memakai skala 0 sampai 100. Skor dibatasi 0 sampai 100. |
| L, lebih rendah lebih baik | `100 × (2 − realisasi ÷ target)` | Target lebih dari 0. Target dan realisasi memakai skala 0 sampai 100. Skor dibatasi 0 sampai 100. |
| Z, kejadian yang ditargetkan nol | 0 kejadian mendapat 100; 1 atau lebih mendapat 0 | Target harus 0. Realisasi harus bilangan bulat dan tidak negatif. |

Setiap indikator harus memiliki bobot lebih dari 0 dan tidak lebih dari 100%. Saat DRAFT atau REVISION, perubahan bobot boleh membuat total sementara bukan 100% agar reviewer dapat memindahkan bobot antarindikator melalui beberapa perubahan. Kontrak hanya dapat diajukan jika total bobot kembali tepat 100%. Semua indikator harus berstatus valid atau tidak berlaku yang disetujui. Indikator valid memerlukan nilai realisasi dan referensi bukti. Target, realisasi, bobot, serta rumus yang dipakai harus tersimpan bersama kontrak.

#### Contoh hitung untuk demo

Angka berikut hanya untuk memperjelas cara kerja prototype, bukan target atau aturan bank yang disahkan.

| Tipe | Target demo | Realisasi demo | Bobot | Skor prototype |
| --- | ---: | ---: | ---: | ---: |
| A | 100 | 110 | 50% | 110 |
| L | 10 | 9 | 30% | 100, karena hasil 110 dibatasi maksimal 100 |
| Z | 0 | 0 | 20% | 100 |

Skor KPI adalah `(110 × 50% + 100 × 30% + 100 × 20%) ÷ 100% = 105`. Jika lima aspek perilaku masing-masing mendapat nilai 3, skor perilaku adalah 100 dan nilai akhir prototype menjadi `105 × 80% + 100 × 20% = 104`. Dengan bukti perilaku sintetis dan syarat mutu terpenuhi, prototype memberi rating Istimewa. Jika indikator Z gagal pada kelompok Bisnis, prototype membatasi rating paling tinggi menjadi Baik meskipun nilai numeriknya tinggi.

### Kelengkapan dan agregasi nilai

- Status tidak berlaku memerlukan alasan dan persetujuan dari peran yang akan ditetapkan. Bobot indikator tersebut tidak masuk ke bobot valid.
- Bobot valid adalah jumlah bobot indikator yang berstatus valid. Prototipe meminta bobot valid sekurangnya 70%.
- Nilai hasil KPI dihitung sebagai `jumlah(skor indikator × bobot indikator) ÷ jumlah bobot valid`.
- Indikator yang masih tertunda menghalangi penghitungan, meskipun bobot valid sudah mencapai 70%.
- Nilai kosong tidak dianggap nol. Perilaku juga harus lengkap sebelum nilai akhir dapat dihitung.

Contoh perilaku dalam kode memakai lima aspek: Integritas, Orientasi layanan, Kerja sama, Kepatuhan kerja, dan Pembelajaran dan perbaikan. Skor aspek 1 sampai 5 dipetakan ke nilai 0, 50, 100, 110, dan 120. Skor perilaku adalah rata-rata lima nilai aspek. Nilai akhir dihitung sebagai `80% × skor hasil KPI + 20% × skor perilaku`.

Rumus gabungan di atas menjelaskan prototipe, bukan perilaku MVP. MVP menghitung bagian skor KPI saja dan menandai versinya sebagai formula demo. `behavior_score`, `final_score`, dan rating bernilai kosong sampai pemilik proses menyetujui bobot, skala perilaku, ambang rating, dan cara penilaian.

API demo saat ini membulatkan skor item dan skor KPI ke tiga desimal; kolom skor item di database juga memakai skala tiga desimal. Prototype HTML mempertahankan presisi penuh saat menghitung skor hasil dan hanya membatasi tampilan ke dua desimal. Ini perbedaan implementasi yang belum menjadi kebijakan. Titik pembulatan perlu diputuskan sebelum angka dipakai untuk hasil resmi.

### Rating dan pembatas kualitas

Ambang bawaan prototype adalah:

| Nilai akhir | Rating bawaan |
| --- | --- |
| Di bawah 75 | Perlu perbaikan |
| 75 sampai kurang dari 90 | Cukup |
| 90 sampai 100 | Baik |
| Di atas 100 sampai kurang dari 103 | Sangat baik |
| 103 atau lebih | Istimewa jika syarat mutu dan bukti terpenuhi |

Dalam kode, indikator bertanda mutu dengan skor di bawah 80 atau indikator Z yang gagal dapat membatasi rating kategori Bisnis sampai Baik. Rating Istimewa juga memerlukan seluruh indikator non-A yang valid mencapai skor minimal 100 serta bukti perilaku. Nilai skor tetap ditampilkan meskipun rating dibatasi.

Ada keputusan kebijakan yang perlu dipastikan sebelum implementasi:

- Apakah bobot hasil dan perilaku benar 80% dan 20%, dan apakah bobot perilaku berlaku untuk semua jabatan?
- Apakah ambang bobot valid 70% layak untuk setiap siklus? Apakah indikator tidak berlaku boleh dikompensasi dengan normalisasi bobot valid?
- Apakah batas rating kualitas hanya berlaku untuk kelompok Bisnis? Kode saat ini tidak menerapkan batas Z yang sama pada kelompok lain.
- Apakah ambang 75, 90, 100, dan 103 serta nama rating akan dipakai? Apakah rating memengaruhi insentif, promosi, atau hanya coaching?
- Siapa yang menilai perilaku, dan bukti apa yang diwajibkan untuk setiap tingkat?
- Pada titik mana nilai dibulatkan, dan bagaimana bank menangani perubahan target setelah siklus dimulai?

Agregasi KPI tingkat cabang, Korwil, divisi, dan bank belum ditentukan oleh prototype. Tetapkan aturan per indikator. Rasio perlu dihitung dari pembilang dan penyebut yang sesuai. Nilai rupiah atau jumlah hanya dijumlahkan bila definisinya sebanding dan tidak terjadi penghitungan ganda.

## 9. Autentikasi, keamanan, dan audit

- Demo MVP memakai selector identitas sintetis di development lokal. SSO dan pemetaan akun uji ke pegawai dummy masuk tahap lanjutan; selector tidak boleh diaktifkan di produksi.
- Seed/reset dan data `demo_only` boleh dipakai di demo lokal serta UAT yang terisolasi. Keduanya tidak boleh tersedia atau dipakai di produksi.
- Semua route `/api/v1` aktif pada prototype hanya menyajikan data demo dan dibatasi ke `local`/`testing`, atau `staging` dengan `DEMO_DATA_ALLOWED=true`; data demo yang terbawa ke produksi tetap ditolak oleh middleware.
- Integrasi SSO bukan gerbang penerimaan MVP demo. Sebelum callback dibuat pada tahap lanjutan, TI perlu menyediakan protokol IdP (OIDC atau SAML), metadata/discovery dan sertifikat, client credentials, URL callback/ACS dan logout, domain aplikasi, klaim identitas, serta akun uji per cakupan akses.
- Callback wajib menggunakan pemeriksaan sesuai protokol yang dikonfigurasi. Untuk OIDC: gunakan Authorization Code flow; ikat `state` dan `nonce` ke transaksi/sesi, gunakan PKCE `S256` bila didukung IdP, lalu validasi signature ID Token, `iss`, `aud`, `exp`, dan kecocokan `nonce`. Untuk SAML 2.0: validasi response/assertion dan signature sesuai profil yang disepakati, lalu periksa `Issuer`, `Audience`, `Recipient`/ACS, `NotOnOrAfter`, dan `InResponseTo` terhadap request yang masih berlaku. Tolak response yang gagal validasi; jangan menerima role aplikasi dari claim IdP. Acuan: [OIDC Core](https://openid.net/specs/openid-connect-core-1_0.html), [OAuth 2.0 Security BCP (RFC 9700)](https://www.rfc-editor.org/rfc/rfc9700.html), dan [SAML 2.0 Web Browser SSO Profile](https://docs.oasis-open.org/security/saml/v2.0/saml-profiles-2.0-os.pdf).
- Fondasi pemetaan SSO menyimpan pasangan `issuer`/`subject` dan hash pencarian pada tabel `sso_identities`; `subject` serta hash disembunyikan dari serialisasi JSON. Resolver hanya menemukan pegawai aktif dengan penugasan aktif. `SsoSessionEstablisher` membentuk sesi melalui guard `web` setelah identitas IdP terverifikasi. `bootstrap/app.php` mengaktifkan `statefulApi()`, dan route API memakai `auth:sanctum` untuk menerima sesi Laravel. Middleware API memeriksa status mapping, pegawai, serta penugasan utama di setiap request, lalu mengakhiri sesi web jika salah satunya dicabut. Validasi protokol dan callback IdP masih menunggu konfigurasi TI. Email bukan kunci permanen.
- Sebelum UAT, tetapkan siapa yang boleh membuat, memeriksa, menonaktifkan, dan mengaudit mapping akun. Usulan sementara: mapping dikelola operator TI/SDM yang ditetapkan, bukan pendaftaran mandiri; kanal provisioning dan pencatatan pelakunya belum disahkan.
- Role dan cakupan akses tetap diturunkan dari penugasan demo yang aktif; klaim IdP tidak boleh melewati matriks kewenangan aplikasi.
- SvelteKit dan API Laravel diperlakukan sebagai aplikasi terpisah. `VITE_API_BASE_URL` mengarahkan frontend ke origin API saat deploy terpisah; nilai kosong memakai proxy Vite lokal. Laravel menyediakan konfigurasi CORS ber-kredensial dengan allowlist `FRONTEND_ORIGINS`; nilai origin UAT, `SANCTUM_STATEFUL_DOMAINS`, dan domain cookie tetap ditetapkan bersama TI. Sanctum memerlukan FE dan API berbagi top-level domain; jika tidak, sepakati pola autentikasi lain sebelum UAT.
- Jika sesi habis, API mengembalikan 401; jika token CSRF tidak berlaku, API mengembalikan 419. Frontend mengarahkan pengguna untuk login ulang atau mengambil token CSRF baru.
- Jangan menyimpan token jangka panjang di browser untuk mengautentikasi frontend pertama milik bank. Jika aplikasi konsumen lain muncul, tentukan skema autentikasinya secara terpisah.
- Simpan bukti di penyimpanan privat. Unduhan harus melewati pemeriksaan hak akses. Jenis file, ukuran, masa simpan, dan pemindaian file mengikuti kebijakan TI bank.
- Catat perubahan target dan hasil tanpa menimpa histori. Penilaian final hanya dapat dibuka ulang melalui proses dengan alasan dan otorisasi.
- Terapkan enkripsi, retensi, pencadangan, dan pemulihan sesuai standar TI dan keamanan bank.

### Penggunaan dan operasi

- Form pengisian KPI harus dapat digunakan pada desktop dan ponsel. Pengelolaan massal dan laporan organisasi dapat mengutamakan desktop.
- Form, tabel, dialog, dan status harus dapat digunakan dengan keyboard serta memiliki label, fokus, dan pesan kesalahan yang jelas.
- Impor CSV, saat ditambahkan setelah MVP, harus memvalidasi format, ID duplikat, referensi unit/jabatan, dan masa berlaku. Sistem harus memberi laporan kesalahan yang dapat diunduh tanpa mengubah baris yang berhasil diproses.
- Versi peramban, sasaran waktu respons, ketersediaan, retensi, RPO/RTO, serta kebijakan sesi mengikuti standar TI bank dan ditetapkan sebelum peluncuran.
- Operasi produksi harus menyediakan pemantauan kegagalan login SSO, impor, unggahan bukti, proses penilaian, ekspor, pencadangan, dan pemulihan.

## 10. Cakupan MVP satu minggu

Targetnya adalah satu alur kerja KPI yang dapat dipakai untuk demo dalam lima hari kerja. Semua data bisnis tetap sintetis dan dapat direset. Login MVP memakai selector user dummy; SSO masuk tahap lanjutan. Siklus demo bulanan memakai tanggal mulai dan akhir yang ditentukan. Laravel menangani REST API, sesi, aturan akses, perubahan status, audit, dan perhitungan. SvelteKit menampilkan alur lewat API JSON.

### Masuk MVP

- Login demo melalui selector identitas sintetis yang sudah tersedia; alur MVP menguji Admin, Supervisor 1, PE/Supervisor 2, dan Pegawai self-only pada divisi serta cabang. User PE Direksi/Korwil dapat tetap ada sebagai data dummy, tetapi alurnya ditunda.
- Seed berisi 3 Direksi, 9 Divisi, 4 Korwil, 28 Cabang, bagian Pemasaran dan Operasional, posisi, penugasan, serta identitas demo. Hubungan atasan dan wilayah adalah contoh sintetis.
- Satu siklus bulanan dengan rentang tanggal eksplisit, katalog placeholder sintetis, dan kontrak demo yang sudah dibuat lewat seed. Katalog resmi ditetapkan Admin pada tahap pengelolaan katalog.
- Dashboard sesuai cakupan akses cabang/divisi berisi jumlah dan status kontrak, halaman KPI saya, detail kontrak, dan daftar tinjauan. MVP tidak menampilkan rata-rata skor lintas unit.
- Pegawai self-only memilih KPI dari katalog yang ditetapkan Admin untuk jabatannya, mengusulkan target, lalu memperbarui realisasi dan referensi bukti sintetis pada kontrak sendiri.
- Supervisor yang berwenang dapat menyesuaikan target atau bobot saat kontrak Draf/Revisi dengan alasan. Backend menyimpan perubahan dan menghitung ulang skor demo.
- Jika Supervisor 1 dan PE/Supervisor 2 tersedia, keduanya meninjau berurutan. Jika hanya satu reviewer tersedia, ia dapat menuntaskan tinjauan dan finalisasi. Pengajuan tanpa reviewer aktif ditolak.
- Backend menghitung skor KPI demo dengan formula prototype yang ditandai `demo_only`. Data yang belum diisi tetap kosong, bukan nol. Skor perilaku, nilai akhir, dan rating belum ditampilkan.
- Audit untuk perubahan target, realisasi, pengajuan, tinjauan, dan finalisasi.
- Selector identitas hanya tersedia di demo lokal. Seed/reset tersedia di demo lokal atau UAT terisolasi; endpoint pemilih identitas ditolak di produksi.

### Setelah MVP

Pengelolaan struktur dan pegawai, impor, editor katalog dan siklus, banding, penilaian perilaku, unggah file, notifikasi, ekspor CSV, dan integrasi SIMPEG/core banking. Katalog resmi dan kebijakan skor juga menunggu persetujuan pemilik proses.

### Layar dan endpoint MVP

| Layar atau tindakan | Endpoint Laravel | Aktor dan aturan |
| --- | --- | --- |
| Login dan logout | `POST /api/v1/demo/session`, `DELETE /api/v1/demo/session` | Pilih user demo dari daftar; selector hanya untuk development lokal. Callback SSO dibuat pada tahap lanjutan. |
| Konteks pengguna | `GET /api/v1/me` | Semua sesi terautentikasi; respons memuat pegawai demo, peran aplikasi, penugasan aktif, dan cakupan. |
| Dashboard | `GET /api/v1/dashboard?cycle_id=...` | Pegawai melihat KPI sendiri; Supervisor 1 dan PE/Supervisor 2 melihat kontrak dalam jalur review cabang/divisinya; Admin mengelola katalog. PE Direksi/Korwil di luar alur MVP. |
| Siklus aktif | `GET /api/v1/cycles` | Frontend mengambil ID siklus berstatus OPEN dari API; ID siklus tidak ditanam di kode frontend. |
| KPI saya | `GET /api/v1/contracts?cycle_id=...` | Pegawai melihat kontrak miliknya. Backend mengambil identitas dari sesi, bukan dari `employee_id` yang dikirim frontend. |
| Antrean tinjauan | `GET /api/v1/contracts?cycle_id=...&status=SUBMITTED` | Reviewer melihat kontrak yang memang ditugaskan kepadanya dalam cakupan akses. |
| Katalog KPI | Endpoint katalog | Semua pengguna dapat melihat katalog yang berlaku; Admin mengelola definisi saat fitur editor diaktifkan. |
| Detail kontrak | `GET /api/v1/contracts/{contractId}` | Pegawai pemilik dan supervisor yang ditugaskan dapat membuka kontrak; PE Direksi/Korwil dirancang pada fase berikutnya. Akses Admin katalog tidak memberi akses hasil otomatis. |
| Riwayat tindakan kontrak | `GET /api/v1/audit-events?contract_id={contractId}` | Pemilik dan supervisor yang ditugaskan dapat melihat histori; akses PE di luar tahap awal. |
| Ubah realisasi atau target/bobot | `PATCH /api/v1/contracts/{contractId}/items/{itemId}` | Pegawai mengubah realisasi sendiri; supervisor berwenang mengubah target/bobot dengan alasan. |
| Komentar | `POST /api/v1/contracts/{contractId}/comments` | Pegawai dan supervisor yang ditugaskan menambah catatan. Direksi tidak mengubah kontrak. |
| Ajukan untuk ditinjau | `POST /api/v1/contracts/{contractId}/submit` | Pegawai pemilik mengajukan KPI dirinya; backend memeriksa kelengkapan dan jalur supervisor. |
| Verifikasi dan revisi | `POST /api/v1/contracts/{contractId}/reviews` | Kedua supervisor meninjau berurutan jika tersedia; satu reviewer aktif dapat meninjau dan memfinalisasi jika hanya satu level tersedia. |

Peran, `employee_id`, dan unit aktif berasal dari sesi serta mapping server. Parameter yang dikirim frontend tidak dapat memperluas cakupan akses. Laravel menghitung skor dan mencatat audit; SvelteKit hanya menampilkan respons API.

### Integrasi SSO setelah MVP demo

Untuk tahap integrasi berikutnya, TI perlu memberikan protokol IdP, metadata/discovery dan sertifikat, client credentials melalui kanal rahasia, domain aplikasi, URL redirect/logout, claim mapping, akses jaringan, dan akun uji. Mapping harus mengarah ke pegawai sintetis; jangan menaruh rahasia atau nilai subject akun nyata di seed atau repository. Jalur login/callback Laravel mengikuti protokol IdP dan berada di luar `/api/v1`; detail rute ditetapkan setelah konfigurasi diterima. Kebutuhan ini tidak menghalangi penyelesaian demo lokal.

### Rencana lima hari kerja

| Hari | Hasil kerja | Status sekarang |
| --- | --- | --- |
| 1 | Kunci subset demo dan payload API; siapkan skema, seed sintetis, dan daftar user dummy. | PRD, kontrak API, seed, sesi lokal, serta selector user tersedia. Aturan bisnis minimum masih perlu divalidasi. |
| 2 | Rapikan akses per peran, endpoint konteks, dan tampilan dashboard. | Fondasi sesi dan otorisasi tersedia; pemeriksaan manual ujung ke ujung belum dilakukan. |
| 3 | Selesaikan seed struktur, siklus, kontrak, dashboard, dan tampilan SvelteKit. | Seed dan layar utama terhubung ke API; typecheck/build pernah lulus. Pemeriksaan browser belum dilakukan. |
| 4 | Hubungkan pengisian realisasi, target, komentar, submit, tinjauan, finalisasi, perhitungan demo, dan audit. | Implementasi awal tersedia untuk alur tersebut; belum diverifikasi ujung ke ujung di browser. |
| 5 | Peragakan alur demo dari login sampai finalisasi dengan beberapa peran sintetis; catat keputusan yang masih terbuka. | Menunggu pemeriksaan manual dan validasi aturan minimum. |

Status di atas merujuk pada artefak repository. Itu belum menjadi persetujuan kebijakan KPI atau bukti bahwa alur demo berjalan penuh.

Rencana lima hari ini untuk demo lokal dengan user dummy. Integrasi SSO memiliki rencana dan jadwal terpisah setelah konfigurasi serta akses IdP tersedia.

## 11. Kriteria penerimaan MVP

1. Seed menyediakan role demo untuk Admin, Supervisor 1, PE/Supervisor 2, dan Pegawai self-only pada divisi serta cabang. Data Direksi dan Korwil boleh tetap sintetis, tetapi alur keduanya tidak termasuk penerimaan tahap ini. Reset hanya tersedia di local development atau UAT terisolasi.
2. API Laravel menyediakan alur MVP melalui JSON `/api/v1`; kontrak API mendokumentasikan request, response, validasi, status, dan akses.
3. SvelteKit menggunakan API untuk dashboard, KPI saya, detail kontrak, dan antrean tinjauan. Frontend tidak membaca database atau menghitung skor final.
4. Selector identitas sintetis menampilkan user demo dan membentuk sesi sesuai peran yang dipilih. Setiap request memeriksa status pegawai dan penugasan utama. Pegawai hanya dapat mengakses KPI sendiri; reviewer dibatasi ke kontrak dalam jalur cabang/divisinya. Callback SSO bukan bagian dari penerimaan demo MVP dan selector ditolak di produksi.
5. Seed demo hanya dapat dijalankan di demo lokal atau UAT terisolasi; produksi menolak seluruh endpoint, seed, dan data `demo_only`.
6. Pegawai self-only memilih KPI yang tersedia untuk jabatannya dan mengusulkan target hanya untuk kontrak sendiri.
7. Pegawai mengubah realisasi, catatan, dan referensi bukti sintetis; nilai kosong tidak diperlakukan sebagai nol.
8. Jika kedua tingkat reviewer tersedia, Supervisor 1 meninjau lebih dulu dan PE/Supervisor 2 memfinalisasi. Jika hanya satu reviewer tersedia, reviewer itu dapat menyelesaikan proses sampai final. Pengajuan tanpa reviewer aktif ditolak.
9. Perubahan target/bobot oleh reviewer wajib memiliki alasan. Perubahan target, bobot, realisasi, dan status tercatat di audit.
10. Backend menghitung skor KPI demo dan menandai formula sebagai draft kebijakan. Skor perilaku, nilai akhir, dan rating tetap kosong. Tidak ada skor yang dipakai sebagai aturan bank resmi.
11. Data pegawai, transaksi, rekening, atau dokumen operasional nyata tidak dimuat ke seed.

MVP demo diterima jika alur tersebut berjalan lokal menggunakan user sintetis sesuai peran dan siklus bulanan berdasar rentang tanggal. Banding, penilaian perilaku, impor master organisasi, integrasi SIMPEG/core banking, login SSO, katalog resmi, dan editor katalog Admin tetap menjadi pekerjaan berikutnya.

## 12. Usulan teknologi

Gunakan Laravel sebagai backend REST API dan SvelteKit sebagai aplikasi frontend yang mengonsumsi API. Laravel mendukung pola API backend untuk aplikasi JavaScript. SvelteKit adalah framework aplikasi dari tim Svelte; shadcn-svelte menyediakan komponen yang dapat ditambahkan dan disesuaikan di source frontend.

Frontend tidak memakai Inertia dan tidak mengakses database Laravel secara langsung. Untuk demo internal, gunakan rendering di browser dan biarkan Laravel menjadi satu-satunya backend bisnis. Jangan membuat endpoint bisnis duplikat melalui SvelteKit server routes. Seluruh tindakan melewati API JSON berversi. Draft kontrak tersedia di `docs/openapi.yaml` dengan OpenAPI 3.2.1. Jangan menaruh aturan skor atau otorisasi sebagai logika frontend.

Struktur kerja yang diusulkan adalah satu repository dengan aplikasi terpisah, misalnya `backend/` untuk Laravel, `frontend/` untuk SvelteKit, dan `docs/` untuk PRD serta kontrak API. Keduanya dapat dibangun dan dirilis terpisah jika kelak tim atau hosting membutuhkannya. Database dan penyimpanan mengikuti standar TI bank.

Catatan komponen: shadcn-svelte adalah port komunitas untuk Svelte, bukan paket resmi shadcn/ui React. Tim perlu menerima perbedaan ekosistem dan dokumentasi tersebut.

Sanctum menjadi opsi sesi untuk SvelteKit sebagai frontend pertama, bukan keputusan autentikasi semua konsumen API masa depan. Setiap frontend lain harus memakai alur yang disetujui untuk kliennya dan tetap melewati otorisasi Laravel.

Referensi teknis: [Laravel API backend](https://laravel.com/framework/docs), [SvelteKit](https://svelte.dev/docs/kit/introduction), [adapter SvelteKit](https://svelte.dev/docs/kit/adapters), [shadcn-svelte untuk SvelteKit](https://www.shadcn-svelte.com/docs/installation/sveltekit), [Laravel Sanctum](https://laravel.com/framework/docs/sanctum), [OpenAPI Specification](https://spec.openapis.org/oas/).

## 13. Keputusan yang perlu divalidasi

### Usulan periode penilaian

Siklus bulanan sudah dipilih. Setiap siklus menyimpan rentang tanggal mulai dan akhir agar kalender kerja dapat diatur. Katalog sumber memiliki indikator dengan jendela ukur 30 hari, hari ke-90, tiga bulan, atau enam bulan. Admin/pemilik KPI perlu menetapkan cara mencatat indikator berjangka lebih panjang di siklus bulanan, misalnya realisasi bulan berjalan atau progres kumulatif. Tanggal batas input dan tinjauan belum ditentukan.

| Keputusan | Status | Usulan sementara | Pihak validasi | Gerbang |
| --- | --- | --- | --- | --- |
| Data awal demo memakai dummy | Disepakati | Semua identitas, unit, target, realisasi, bukti, komentar, dan histori demo sintetis; data dapat dihapus/reset | Pemilik produk | Seed MVP |
| Backend Laravel REST API dan frontend SvelteKit dengan shadcn-svelte | Disepakati sebagai arah teknis | Frontend terpisah mengonsumsi API berversi; tidak memakai Inertia | Pemilik produk dan teknis | Scaffold awal tersedia; lanjutkan implementasi bertahap |
| Ruang lingkup MVP satu minggu | Target disepakati | Lima hari kerja untuk data sintetis, selector user dummy, dan satu alur dari pengisian sampai finalisasi | Pemilik produk | Keputusan aturan bisnis minimum |
| Peran demo | Disepakati | Admin mengelola katalog; Kepala Bidang pusat/cabang menjadi Supervisor 1; Kepala Divisi dan Kepala Cabang adalah PE/Supervisor 2; Staf dan Kepala Sub Bidang self-only. Korwil dan Direksi juga PE, tetapi di luar fokus MVP awal. | Pemilik produk | Seed dan otorisasi divisi/cabang |
| Alur reviewer | Disepakati | Jika Supervisor 1 dan PE/Supervisor 2 tersedia, keduanya meninjau berurutan. Jika hanya satu level tersedia, satu reviewer tersebut cukup untuk meninjau dan memfinalisasi. Tanpa reviewer aktif, pengajuan ditolak. | Pemilik produk | Terapkan pada alur review |
| Isi katalog KPI demo | Disepakati untuk demo | 54 definisi placeholder tetap sintetis; Admin bertanggung jawab menetapkan katalog resmi setelah disahkan. Editor katalog Admin belum tersedia. | Pemilik produk | Katalog seed sekarang; editor Admin tahap berikutnya |
| Periode penilaian | Disepakati | Siklus bulanan memakai rentang tanggal mulai/akhir yang diatur per siklus; tenggat input dan review masih terbuka | Pemilik produk | Ubah seed tahunan menjadi siklus bulanan |
| Rumus A/Q/L/Z, bobot, batas skor, dan rating | Belum disahkan | MVP hanya memakai formula bertanda `demo_only`; skor perilaku, nilai akhir, dan rating tetap kosong | Pemilik proses KPI, SDM, dan perwakilan unit | Sebelum pemakaian dengan pegawai nyata |
| Status indikator tidak berlaku | Belum ditentukan | Peran pengaju dan penyetuju `NOT_APPLICABLE`, alasan wajib, serta perlakuan bobot perlu ditetapkan; bukan kewenangan Admin katalog secara otomatis | Pemilik KPI tiap unit dan SDM | Sebelum status ini dipakai |
| Kontrak KPI individu atau unit dan penurunan target | Belum diputuskan | MVP saat ini memakai kontrak individu. Kontrak unit, pembagian target ke pegawai, dan agregasi unit memerlukan aturan pemilik target dan rumus per indikator | Pemilik proses KPI, SDM, dan pimpinan unit | Sebelum target unit masuk MVP lanjutan |
| PE Direksi dan Korwil | Disepakati untuk tahap lanjutan | Direksi dan Korwil termasuk PE. Jalur review dan cakupan masing-masing belum dirancang karena MVP difokuskan pada divisi dan cabang. | Sponsor dan pemilik proses KPI | Sebelum fase Korwil/Direksi |
| Jumlah Bidang dan Subbidang pada tiap Divisi | Belum diputuskan | Seed demo memakai satu Bidang dan satu Subbidang per Divisi untuk menguji hierarki; angka ini bukan klaim tentang struktur resmi | SDM/organisasi | Sebelum struktur nyata diimpor |
| Pemetaan Cabang ke Korwil | Belum diputuskan | Seed demo membagi tujuh cabang ke tiap Korwil sebagai contoh; pembagian resmi harus mengikuti data organisasi | SDM/organisasi | Sebelum struktur nyata diimpor |
| Penyedia SSO, protokol, metadata, akun uji, dan domain aplikasi | Menunggu TI | Ikuti IdP yang berlaku; petakan `issuer` dan `subject` ke identitas demo; rahasia tidak masuk repository | TI/pengelola IdP | Sebelum tahap integrasi SSO |
| Provisioning dan pencabutan mapping SSO | Belum ditentukan | Tetapkan operator, pemeriksa, alasan perubahan, bukti audit, serta proses menonaktifkan mapping saat akses dicabut. Usulan mapping non-self-service masih perlu disahkan. | TI/pengelola IdP dan SDM | Sebelum UAT SSO |
| Sumber resmi struktur dan pegawai | Belum diputuskan | Gunakan seed sintetis untuk MVP; pilih SIMPEG atau sumber resmi lain sebelum impor nyata | SDM/organisasi dan TI | Sebelum impor data nyata |
| Alur Korwil/Direksi dan pejabat sementara | Di luar fokus tahap awal | Keduanya PE; cakupan, urutan review, dan delegasi diputuskan pada fase berikutnya | SDM/organisasi dan pemilik proses KPI | Sebelum fase Korwil/Direksi |
| Hak baca direktori pegawai (`GET /employees`) | Belum diputuskan | OpenAPI menyebut pencarian dalam cakupan, sementara aturan PRD membatasi pimpinan pada ringkasan dan kontrak yang berwenang dilihat. Endpoint tetap rencana; filter unit tidak dengan sendirinya memberi hak membaca data pegawai. | SDM/organisasi, pemilik proses KPI, dan keamanan | Sebelum endpoint diaktifkan |
| Definisi KPI untuk jabatan yang belum tercakup | Belum lengkap | Placeholder hanya untuk demo; jangan menyalin KPI dari jabatan lain sebagai kebijakan final | Pemilik KPI tiap unit dan SDM | Sebelum peluncuran resmi |
| Unggah bukti, retensi, dan lokasi penyimpanan | Belum diputuskan | MVP menyimpan referensi teks sintetis; unggah berkas menunggu kebijakan akses dan retensi | TI/keamanan dan SDM | Sebelum fitur unggah |
| Hosting dan adapter frontend | Usulan | Gunakan Node server/container dengan `adapter-node` selama host khusus belum dipilih | Teknis dan TI | Sebelum deployment bersama |
| Kelompok pilot dan cakupan UAT | Belum dikunci | Pilih akun uji yang mencakup Admin, Direksi, Supervisor 1/2, Korwil, serta Pegawai | Sponsor produk, SDM, dan TI | Sebelum UAT |

### Pertanyaan untuk mengunci aturan bisnis

Gunakan daftar ini dalam sesi dengan pemilik proses KPI dan SDM. Simpan jawaban bersama nama pengambil keputusan dan tanggal persetujuan. Jawaban yang belum disepakati tetap menjadi batas implementasi, bukan asumsi untuk data nyata.

| Urutan | Pertanyaan | Keputusan atau bahan yang dicatat |
| --- | --- | --- |
| 1 | Untuk tenggat MVP satu minggu, alur minimum apa yang wajib selesai dan siapa yang menerima demonya? | Daftar alur wajib dan pihak yang menyetujui demo |
| 2 | Berapa Bidang dan Subbidang resmi di tiap Divisi, siapa atasannya, dan bagaimana 28 Cabang dibagi ke empat Korwil? | Pohon organisasi dan daftar penugasan yang disetujui SDM |
| 3 | Apakah kontrak KPI diberikan per pegawai, per unit, atau keduanya? Jika target unit diturunkan, siapa pemilik target dan bagaimana pembagiannya? | Jenis kontrak, aturan penurunan target, dan contoh yang disetujui |
| 4 | Rentang tanggal bulanan sudah dipilih; kapan batas input dan review? Bagaimana mengelola indikator dengan jendela ukur lebih dari satu bulan? | Tenggat input/review dan aturan pelaporan indikator jangka panjang |
| 5 | Untuk tiap KPI, bagaimana rumus, arah nilai yang baik, batas skor, perlakuan nilai kosong atau nol, dan syarat mutu? | Formula per KPI beserta contoh hitung yang disetujui pemilik indikator |
| 6 | Siapa yang menilai KPI diri Kepala Divisi dan Kepala Cabang pada fase cabang/divisi? | Reviewer KPI untuk PE tingkat Supervisor 2 |
| 7 | Bukti apa yang wajib dilampirkan untuk setiap indikator, dan di mana bukti disimpan serta berapa lama? | Jenis bukti, klasifikasi akses, lokasi simpan, dan retensi |
| 8 | Peran mana yang boleh mencari atau melihat detail pegawai melalui direktori, kolom apa yang boleh ditampilkan, dan apakah batas pencarian mengikuti unit atau penugasan reviewer? | Matriks akses direktori pegawai dan contoh respons per peran |
| 9 | Dalam fase berikutnya, pada tingkat mana Korwil dan Direksi meninjau hasil sebagai PE? | Jalur review PE lintas wilayah dan tingkat Direksi |
| 10 | Dalam kondisi apa indikator boleh berstatus tidak berlaku, siapa yang mengajukan dan menyetujuinya, dan bagaimana bobotnya diperlakukan? | Kriteria pengecualian, alur persetujuan, dan contoh hitung bobot valid |

Sesi ini belum mengesahkan rumus atau struktur. Hasilnya menjadi masukan untuk memperbarui matriks keputusan dan kriteria penerimaan sebelum data nyata atau nilai akhir dipakai.

## 14. Catatan rekonsiliasi sumber

Workbook dan katalog pada HTML cocok pada 96 indikator, bobot, tipe, target, definisi, sumber, catatan penerapan, dan tanda kualitas. Keenam indikator pada masing-masing dari 16 jabatan memiliki total bobot 100%; ini memeriksa konsistensi aritmetika, bukan pengesahan bobot.

Satu perbedaan isi ditemukan pada sel `J8` untuk KPI `01.01` AO Kredit: formula di workbook menghasilkan nilai tersimpan `4.166666666666667` di kolom satuan, sedangkan HTML mencantumkan `Rp`. Kolom tersebut seharusnya berisi satuan; pemilik katalog perlu mengonfirmasi koreksi workbook.

Label versi masih perlu diklarifikasi. Workbook menyebut katalog HTML `1.0.0`, file HTML menyebut prototype `1.1.0`, dan variabel `VERSION='1.0.0'` dipakai untuk memeriksa versi data browser. Perbedaan ini belum cukup untuk menyimpulkan versi katalog workbook tertinggal.

Prototype HTML hanya menyimpan data di browser dan menjalankan pergantian peran simulasi. Prototype tersebut menjadi referensi alur dan rumus, bukan sumber data untuk penggunaan produksi.

## 15. Progres penyusunan

Pembaruan: 10 November 2026.

- [x] Periksa workbook dan prototype HTML, termasuk kecocokan 96 indikator dan total bobot 100% untuk setiap jabatan.
- [x] Catat perbedaan satuan pada KPI AO Kredit `01.01`, bedakan label versi katalog dan versi prototype yang belum terkonfirmasi, serta catat cakupan 16 jabatan.
- [x] Cocokkan rumus A/Q/L/Z dan pembobotan HTML dengan kalkulator Laravel; catat perbedaan pembulatan tiga desimal pada API versus presisi penuh pada perhitungan prototype.
- [x] Susun draft kebutuhan, alur, akses organisasi, SSO, aturan skor awal, serta rencana MVP.
- [x] Rinci formula, bobot valid, penilaian perilaku, rating, dan pembatas mutu yang saat ini ada di prototype.
- [x] Tambahkan contoh hitung skor menggunakan angka sintetis agar usulan formula bisa divalidasi bersama pemilik proses.
- [x] Rinci hubungan data, snapshot penugasan, versi KPI, dan transisi status penilaian.
- [x] Tetapkan data dummy sebagai isi MVP dan Laravel REST API dengan frontend SvelteKit/shadcn-svelte sebagai arah teknis.
- [x] Buat draft kontrak OpenAPI untuk endpoint utama, payload demo, dan format kesalahan API.
- [x] Selaraskan kontrak API dengan kebutuhan pengelolaan unit, pegawai, dan riwayat penugasan oleh SDM demo.
- [x] Lengkapi endpoint pengelolaan jabatan tanpa menghapus referensi historis.
- [x] Tambahkan pengelolaan versi KPI draft dan siklus penilaian ke kontrak API.
- [x] Tetapkan seluruh KPI seed sebagai placeholder sintetis `demo_only`; katalog pada workbook tetap menjadi referensi analisis dan tidak dimuat ke database demo.
- [x] Batasi audit event ke kontrak dan item demo untuk seluruh peran; SDM tetap lintas kontrak demo, sedangkan pegawai/reviewer dibatasi ke kontrak yang berwenang mereka lihat.
- [x] Tambahkan endpoint dan skema penilaian perilaku; penilai produksi tetap menjadi keputusan bisnis terbuka.
- [x] Tambahkan komentar koreksi kontrak dan perubahan target/bobot yang dibatasi peran serta alasan.
- [x] Definisikan matriks akses draft dan verifikasi oleh atasan/reviewer aktif; mapping organisasi nyata tetap menunggu konfirmasi.
- [x] Susun rencana seed dummy yang dapat direset, termasuk identitas demo dan mapping organisasi sintetis.
- [x] Susun usulan siklus tahunan dengan pantauan bulanan dan tinjauan triwulanan berdasarkan waktu pematangan KPI di katalog.
- [x] Tetapkan target MVP satu minggu memakai data sintetis dan selector user dummy; SSO ditunda ke tahap integrasi setelah demo.
- [x] Siapkan seed lokal 116 unit, 10 jabatan, 128 identitas, 54 definisi KPI dummy, dan 120 kontrak contoh; empat identitas staf/Kepala Sub Bidang dibiarkan tanpa kontrak agar alur susun KPI sendiri dapat dicoba. Kontrak Direksi tetap menunggu keputusan reviewer.
- [x] Cocokkan database lokal secara baca-saja dengan struktur demo: 3 Direksi, 9 Kepala Divisi, 9 Kepala Bidang, 9 Kepala Sub Bidang, 4 Korwil, 28 Kepala Cabang, 28 staf Pemasaran, 28 staf Operasional, dan dua bagian pada setiap cabang; tiap Korwil menaungi tujuh cabang sintetis.
- [x] Tambahkan pemeriksaan integritas seed untuk jumlah dan relasi induk unit, jumlah pegawai per jabatan, jalur atasan langsung pusat/cabang, dua bagian per cabang, dan tujuh cabang per Korwil.
- [x] Tegakkan jumlah seed per tabel dan pastikan setiap unit, jabatan, pegawai, penugasan, definisi KPI, siklus, serta kontrak ber-ID DEMO berstatus demo_only.
- [x] Pastikan semua kontrak seed lolos scope relasi demo yang sama dengan dashboard, audit, detail, dan workflow.
- [x] Batasi kalkulator demo ke tipe formula A/Q/L/Z yang dikenal; tipe lain tidak menghasilkan skor.
- [x] Batasi status `NOT_APPLICABLE` demo kepada SDM dengan alasan wajib; pengajuan tetap ditolak jika ada indikator yang belum lengkap. Kebijakan produksi masih perlu disahkan.
- [x] Izinkan bobot disesuaikan bertahap pada DRAFT/REVISION; pengajuan tetap ditolak sampai total bobot 100%.
- [x] Isi contoh kontrak draf dengan dua komentar, perubahan target beralasan, dan 198 audit event untuk perubahan item serta transisi review.
- [x] Buat sesi identitas demo, konteks akses, dashboard, katalog/tree baca, dan API alur kontrak Laravel.
- [x] Hubungkan layar SvelteKit ke REST API untuk memilih identitas, melihat ringkasan, mengisi, mengajukan, meninjau, dan finalisasi kontrak.
- [x] Tampilkan riwayat tindakan per kontrak dari endpoint audit yang dibatasi hak akses.
- [x] Petakan layar dan tindakan MVP ke endpoint Laravel serta batas peran server.
- [x] Catat sesi cookie demo dan prasyarat CORS/CSRF; rincian sesi SSO ditetapkan saat integrasi IdP.
- [x] Buat tabel identitas SSO, resolver ke pegawai aktif, dan layanan pembentukan sesi setelah klaim terverifikasi; mapping gagal membersihkan autentikasi lama. Migrasi lokal diterapkan, callback dan validasi protokol belum dibuat.
- [x] Aktifkan middleware stateful Sanctum untuk API dan gunakan sesi guard `web` bagi identitas SSO terverifikasi; callback IdP masih menunggu protokol dan konfigurasi TI.
- [x] Catat persyaratan validasi callback OIDC dan SAML yang terpisah berdasarkan spesifikasi resmi; protokol aktual masih menunggu TI.
- [x] Sembunyikan nilai subject dan hash mapping SSO dari serialisasi JSON; callback tetap menunggu protokol dan konfigurasi TI.
- [x] Periksa status pegawai, penugasan utama, dan mapping SSO pada setiap route API terautentikasi; sesi web diakhiri jika akses dicabut.
- [x] Tegaskan bahwa pemilih identitas demo hanya aktif lokal; seed UAT perlu akun uji SSO, dan reset seed menghapus mapping identitas yang perlu diterapkan ulang.
- [x] Batasi daftar identitas selector demo ke pegawai aktif dengan penugasan, jabatan, dan unit demo yang aktif.
- [x] Batasi sesi API terautentikasi dan resolver mapping SSO ke pegawai `demo_only`; akses staging dengan flag demo tidak dapat memakai akun pegawai non-demo.
- [x] Batasi konteks `/me` dan cakupan unit ke penugasan, jabatan, serta unit `demo_only`, termasuk unit turunan dan cabang Korwil.
- [x] Satukan syarat penugasan demo pada query model yang dipakai selector identitas, middleware API, cakupan, dan resolver SSO.
- [x] Batasi daftar dan detail definisi KPI ke jabatan demo, selain memastikan definisi KPI sendiri berstatus demo_only.
- [x] Terapkan satu scope kontrak demo pada dashboard, daftar/detail kontrak, audit, dan alur perubahan; scope memeriksa siklus, pegawai, reviewer, jabatan, unit, definisi indikator, komentar, dan tinjauan terkait.
- [x] Konfigurasikan base URL API frontend lewat `VITE_API_BASE_URL`; nilai kosong memakai proxy lokal.
- [x] Pisahkan client HTTP, CSRF Sanctum, dan normalisasi error ke `frontend/src/lib/api.ts`; komponen Svelte memakai client REST bersama.
- [x] Sediakan konfigurasi CORS Laravel ber-kredensial dengan allowlist origin dari `FRONTEND_ORIGINS`; origin dan domain cookie UAT tetap menunggu TI.
- [x] Batasi semua route API demo ke local/testing atau staging terisolasi dengan `DEMO_DATA_ALLOWED=true`, termasuk jika baris demo ada di database produksi.
- [x] Jalankan pemeriksaan sintaks PHP pada 56 file sumber backend dan pastikan 16 route API serta satu route CSRF terdaftar.
- [x] Ulangi `svelte-check` dan production build setelah konfigurasi base URL API; `svelte-check` menemukan 0 error dan 0 warning, build berhasil.
- [x] Susun gerbang validasi bisnis dengan usulan sementara, pihak validasi, dan tahap yang memerlukannya.
- [x] Tambahkan agenda pertanyaan validasi bisnis dan tandai jumlah Bidang/Subbidang serta pembagian Korwil seed sebagai data sintetis.
- [x] Tetapkan siklus bulanan dengan rentang tanggal per periode.
- [ ] Tetapkan tenggat input/review dan aturan untuk indikator dengan jendela ukur lebih dari satu bulan.
- [ ] Validasi rumus, titik pembulatan, bobot, dan rating dengan pemilik proses.
- [ ] Putuskan apakah MVP lanjutan membutuhkan kontrak KPI unit dan aturan penurunan target ke pegawai.
- [x] Tetapkan aturan review: kedua level wajib bila tersedia; satu reviewer cukup bila hanya satu level tersedia. Jalur PE Korwil/Direksi dicatat untuk fase berikutnya.
- [ ] Terapkan role matrix penuh pada seed, OpenAPI, otorisasi API, dan selector. Self-assignment PEGAWAI/KEPALA_SUB_BIDANG sudah tersedia; reviewer Supervisor 1/2 dan cakupan Admin/cabang masih perlu diterapkan. Seed masih memakai satu reviewer untuk kontrak contoh dan belum memiliki Kepala Bidang Operasional/Pemasaran cabang.
- [x] Ganti siklus tahunan aktif dengan siklus demo bulanan berbasis tanggal mulai/akhir. Kontrak historis pada siklus lama dipertahankan.
- [ ] Tambahkan editor katalog Admin setelah aturan akses katalog disepakati; katalog demo saat ini masih berupa seed placeholder.
- [ ] Konfirmasi struktur atasan, penugasan Korwil ke cabang, serta sumber master pegawai.
- [ ] Konfirmasi penyedia SSO, protokol, dan akses lingkungan uji untuk tahap integrasi SSO.
- [ ] Tetapkan pemilik proses pembuatan, pemeriksaan, pencabutan, dan audit mapping SSO sebelum UAT SSO.
- [ ] Lengkapi dan sahkan katalog KPI untuk jabatan yang belum tercakup.
- [ ] Kunci cakupan dan kriteria penerimaan MVP.
- [ ] Konfirmasi target host dengan TI dan terapkan usulan `adapter-node`; build saat ini memakai `adapter-auto`.
- [x] Batasi ringkasan dashboard pegawai ke kontrak miliknya; cakupan hierarki tetap berlaku untuk peran pimpinan dan SDM.
- [x] Hitung kontrak dengan KPI siap dari hasil kalkulator skor, termasuk validitas bobot dan item, bukan hanya status PENDING.
- [x] Turunkan cakupan Korwil dari cabang aktif yang ditetapkan melalui penugasan `korwil_unit_id`, lalu sertakan bagian di bawah cabang tersebut.
- [x] Selaraskan batas akses detail kontrak dan audit dengan matriks: pemilik, reviewer tercatat, dan SDM; pimpinan lain mendapat ringkasan dashboard.
- [x] Cocokkan metode dan path OpenAPI dengan route Laravel; 17 operasi tersedia, 29 operasi berstatus rencana, dan tidak ada route API yang tertinggal dari spesifikasi.
- [x] Selaraskan skema request/response 17 route Laravel yang aktif berdasarkan validasi dan serializer; filter kontrak, parameter pohon unit, serialisasi siklus, dan self-assignment kontrak sudah dicocokkan.
- [x] Cantumkan presisi tiga desimal pada `ContractItem.score` dan `ScoreSummary.kpi_score` di OpenAPI sesuai kalkulator demo.
- [x] Selaraskan skema sesi OpenAPI dengan konfigurasi Laravel: local dan UAT memakai sesi Laravel; nama default `laravel-session` berasal dari `APP_NAME=Laravel`, dan `SESSION_COOKIE` dapat menggantinya.
- [x] Tambahkan contoh request untuk sesi demo, self-assignment, isi item, komentar, submit, review, finalisasi, audit, dan bentuk error di `docs/api_examples.md` menggunakan ID seed sintetis.
- [x] Tambahkan README root berisi prasyarat dan langkah menjalankan Laravel, SQLite, seed demo, serta frontend lokal.
- [x] Cocokkan ID contoh terhadap database lokal: pemilik, reviewer, dan SDM aktif; kontrak `DEMO-CONTRACT-0001` berstatus DRAFT dan item 03 bertipe Q dengan target 95.
- [x] Cocokkan request HTTP contoh dengan route Laravel aktif; tidak ada path atau metode contoh yang belum tersedia.
- [x] Tinjau `GET /employees`: tetap di luar MVP dan berstatus rencana sampai hak baca direktori diselaraskan dengan aturan pimpinan yang hanya melihat ringkasan serta kontrak yang berwenang.
- [x] Tambahkan self-assignment draf KPI untuk PEGAWAI dan KEPALA_SUB_BIDANG: katalog terbit sesuai jabatan, target diisi pemilik, bobot dinormalisasi server, dan satu kontrak per orang per siklus bulanan.
- [ ] Jalankan contoh API di browser; 29 operasi bertanda rencana ditinjau kembali sebelum diimplementasikan.
- [ ] Periksa alur demo di browser dari login lokal sampai finalisasi kontrak. Pemeriksaan belum dilakukan karena peluncuran Edge headless diblokir execution policy.

Tahap saat ini: **Self-assignment draf dan siklus demo bulanan sudah tersedia.** Seed masih menyisakan 120 kontrak contoh dengan reviewer lama; empat akun disiapkan untuk membuat kontrak sendiri. Role matrix penuh, reviewer Supervisor 1/2, dan pengajuan setelah reviewer ditetapkan belum selesai. Pemeriksaan browser belum dilakukan. SSO tetap tahap lanjutan.

## 16. Papan progres

| Tahap | Status | Selesai jika | Prasyarat |
| --- | --- | --- | --- |
| 0. Konsep dan draft | Siap ditinjau | PRD, OpenAPI, alur akses, dan seed demo dibaca serta disepakati sebagai dasar kerja | Tinjauan pemilik produk |
| 1. Validasi bisnis | Siklus, role mapping, dan aturan reviewer disepakati; beberapa keputusan terbuka | Tetapkan tenggat bulanan, formula, pengecualian KPI, dan reviewer KPI diri Kepala Divisi/Kepala Cabang. PE Korwil/Direksi dikerjakan pada fase setelah cabang/divisi. | Pemilik produk dan pemilik proses KPI |
| 2. Laravel API dan seed demo | Self-assignment dan siklus bulanan tersedia; role/reviewer penuh belum selesai | Terapkan role Admin, Supervisor 1/2, serta cakupan cabang/divisi penuh; tetapkan reviewer berurutan sesuai ketersediaan. Self-assignment saat ini hanya untuk PEGAWAI/KEPALA_SUB_BIDANG dan kontrak baru tertahan di DRAFT sampai reviewer routing tersedia. API memiliki 17 route aktif; OpenAPI mencatat 29 operasi rencana. | Pemetaan reviewer untuk KPI Kepala Divisi/Kepala Cabang |
| 3. Frontend SvelteKit | Typecheck dan build lulus; layar self-assignment tersedia | Lengkapi akses Admin, Supervisor 1/2, dashboard, detail, pengisian, review, dan finalisasi untuk cabang/divisi. Alur PE Direksi/Korwil ditunda. Pemeriksaan browser belum dilakukan. Usulan adapter deploy: `adapter-node`, menunggu host TI. | Role matrix dan reviewer API; pilihan host |
| 4. Pemeriksaan demo lokal | Belum diverifikasi ujung ke ujung | User dummy dapat dipilih untuk mencoba batas akses tiap role, siklus tanggal, susun dan isi KPI sendiri, serta tinjauan supervisor | Reviewer routing dan alur submit |
| 5. Integrasi SSO | Fondasi mapping siap; callback belum dibuat | Callback IdP, pemetaan akun uji ke pegawai demo, sesi, dan otorisasi teruji di lingkungan UAT | Protokol, metadata, akun uji IdP, domain aplikasi, proses provisioning, dan akses TI/SDM |

Urutan berikutnya: tetapkan reviewer Supervisor 1/2 dengan fallback satu reviewer bila hanya satu tersedia; setelah itu buka pengajuan untuk kontrak baru, lalu periksa susun KPI dan alur review di browser. Target lima hari tetap memakai data sintetis; integrasi SSO dijadwalkan terpisah.
