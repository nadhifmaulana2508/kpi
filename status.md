# Status Pengembangan KPI Perbankan

Pembaruan terakhir: 16 November 2026

## Tahap aktif

UI/UX step 27, inheritance mapping jabatan Aktif ke self-assignment Staf Cabang.

Status: selesai untuk potongan layar ini.

## Arah desain yang dipakai

Design Read: mapping jabatan menjadi kontrak visual antara Admin dan self-assignment staf, dengan status sumber yang terlihat dan perubahan yang baru berlaku setelah disimpan.

- ENERGY 1: tenang dan fokus pada tugas masuk.
- RHYTHM 2: panel konteks dan panel pilihan memberi dua tingkat informasi.
- MOTION 1: hanya state hover, active, focus, dan loading.
- Warna utama memakai navy untuk konteks kerja, putih untuk area tugas, dan gold hanya sebagai penanda lingkungan demo atau fokus utama.
- Tidak menambahkan logo gambar, avatar foto, statistik, atau ilustrasi baru.

## Yang selesai

- Layar masuk diubah menjadi dua area yang jelas: konteks KPI di kiri dan pemilihan identitas di kanan.
- Konteks layar menampilkan periode yang diatur Admin, cakupan Pusat/Korwil/satu cabang, serta penanda bahwa data masih sintetis.
- Setelah identitas dipilih, ringkasan identitas tampil sebelum tombol masuk digunakan.
- Daftar user dummy dipersempit menjadi satu skenario: enam user Pusat, satu Korwil, dan tiga user Cabang Contoh 001.
- Identitas dikelompokkan menjadi Kantor Pusat, Wilayah Contoh, dan Cabang Contoh 001 agar role mudah dipilih tanpa daftar organisasi yang terlalu panjang.
- Label `PEGAWAI` di UI disederhanakan menjadi `Staf`; unit Cabang tetap membedakan Pemasaran dan Operasional.
- Periode 3 bulan tetap menjadi default rancangan sesuai PRD. Opsi 1, 3, 6, dan 12 bulan dicatat sebagai konfigurasi Admin untuk prototipe berikutnya, belum sebagai pengaturan tersimpan.
- Layar Admin KPI sekarang menyediakan selector cadence 1, 3, 6, dan 12 bulan dengan default 3 bulan.
- Preview periode menghitung tanggal mulai dan tanggal selesai dari cadence yang dipilih.
- Status awal dapat dipreview sebagai Draft, Open, atau Closed dengan penjelasan konsekuensi operasionalnya.
- Tombol `Simpan periode dummy` menambahkan periode ke daftar state frontend; refresh browser akan mengembalikan data awal karena belum memakai API.
- Riwayat tiga periode contoh ditampilkan agar admin dapat memilih dan memuat ulang konfigurasi periode ke form.
- Step ini tidak menambah endpoint atau tabel; semua aksi periode masih berada di state frontend untuk prototipe.
- Fokus MVP dikunci ke satu Cabang Contoh 001 dengan dua jalur staf: Pemasaran/Bisnis dan Operasional/Non-bisnis.
- Layar self-assignment menampilkan konteks jalur kerja, katalog KPI yang sesuai, dan tiga tahap: pilih KPI, isi target, lalu ajukan.
- Nama KPI demo dibuat lebih mudah dipahami per jalur, tetapi tetap diberi penanda `demo` dan belum dianggap katalog resmi.
- Self-assignment tetap memilih dari katalog Admin; staf tidak membuat KPI bebas dari form.
- Layar `Riwayat Penilaian` sekarang memfilter history sesuai jalur Pemasaran/Bisnis atau Operasional/Non-bisnis.
- Riwayat dummy mencakup empat status: Draft, Diajukan, Revisi, dan Final.
- Detail read-only menampilkan rentang periode, skor demo, reviewer, tanggal submit/final, catatan proses, target, realisasi, dan skor per indikator.
- Tidak ada kontrol edit pada history; perubahan tetap dilakukan dari alur KPI aktif.
- Dashboard Staf Cabang sekarang menggantikan ringkasan generik dengan konteks jalur kerja Pemasaran atau Operasional.
- Dashboard menampilkan periode aktif, tugas berikutnya, deadline demo, status self-assignment, progres indikator, target versus realisasi, dan tautan ke riwayat.
- CTA dashboard mengarahkan staf ke self-assignment atau melanjutkan revisi/draf sesuai status kontrak aktif.
- Halaman `KPI saya` sekarang menjadi ruang kerja indikator aktif untuk Staf Cabang.
- Jika belum ada kontrak, halaman menampilkan empty state dan CTA kembali ke self-assignment.
- Jika kontrak tersedia, halaman menampilkan status, progres indikator, realisasi terisi, dan membuka detail KPI yang dapat diisi per indikator.
- Detail KPI mempertahankan kontrol target, realisasi, bukti, catatan, status kelengkapan, dan histori tindakan yang sudah tersedia.
- Self-assessment sekarang memiliki tiga tahap: Realisasi, Bukti, dan Review.
- Tahap berikutnya memvalidasi realisasi, bukti, dan perubahan yang belum disimpan sebelum staf dapat lanjut.
- Tahap Review menampilkan ringkasan jumlah indikator, realisasi, bukti, reviewer, dan alasan blokir jika belum siap.
- State DRAFT dan REVISION memakai alur self-assessment; status SUBMITTED/FINAL tetap read-only untuk staf.
- Inbox evaluasi Kepala Cabang memakai empat tugas dummy dari dua jalur: Pemasaran/Bisnis dan Operasional/Non-bisnis.
- Filter inbox mencakup jalur, status Diajukan/Revisi, dan usia tugas 0-3, 4-7, atau 8+ hari.
- Daftar tugas menampilkan pegawai, jabatan, periode, tanggal pengajuan, usia antrean, dan deadline review.
- Detail tugas menampilkan skor demo, status, snapshot target-realisasi per indikator, serta catatan proses; keputusan reviewer berada di panel terpisah.
- Pada step 8, tombol detail review masih disabled sebagai penanda alur berikutnya; step 9 sekarang mengaktifkan prototipe keputusan dummy di detail tugas.
- Step ini tidak menambah endpoint atau tabel; inbox masih memakai data dummy frontend dan endpoint workspace yang sudah ada.
- Detail review sekarang menampilkan snapshot indikator per item: target, realisasi, skor demo, referensi bukti, dan catatan staf.
- Reviewer dapat menulis catatan wajib lalu memilih `Minta revisi` atau `Verifikasi` sebagai state dummy di browser.
- State keputusan dummy tetap terlihat pada tugas yang dipilih dan tidak mengubah status backend atau kontrak API.
- Tombol keputusan diberi validasi catatan kosong dan state disabled agar reviewer tidak menyimpan keputusan tanpa alasan.
- Menu `Monitoring` sekarang tersedia khusus untuk user Admin KPI / SDM pada desktop dan mobile.
- Monitoring dummy menampilkan periode aktif Triwulan IV 2026 dengan cakupan Cabang Contoh 001.
- Ringkasan monitoring menampilkan total kontrak, kontrak menunggu review, kontrak melewati deadline, dan kontrak siap finalisasi.
- Breakdown status mencakup Draf, Diajukan, Revisi, Terverifikasi, dan Final.
- Breakdown jalur membandingkan Pemasaran/Bisnis dan Operasional/Non-bisnis dengan indikator proporsi kontrak.
- Tabel perhatian Admin memfilter jalur dan status non-final serta menampilkan deadline, tanggal pengajuan, skor demo, dan alasan perhatian.
- Step ini tidak menambah endpoint atau tabel; seluruh angka monitoring masih berasal dari data dummy frontend.
- Menu `Katalog KPI` sekarang tersedia khusus untuk user Admin KPI / SDM pada desktop dan mobile.
- Katalog dummy membedakan indikator jalur Pemasaran/Bisnis dan Operasional/Non-bisnis.
- Setiap indikator menampilkan kode, kelompok, tipe, target, bobot, sumber data demo, dan state Draft/Aktif.
- Filter katalog mencakup jalur dan state agar admin dapat melihat indikator yang siap dipakai atau masih disiapkan.
- Form tambah indikator frontend-only menyediakan jalur, kode opsional, kelompok, nama, tipe, unit, target, bobot, sumber data, dan state awal.
- Validasi form menolak field wajib yang kosong, target tidak sesuai tipe, bobot di luar rentang, dan kode duplikat.
- Indikator baru langsung masuk ke daftar katalog dummy dan hilang setelah refresh karena belum tersambung API.
- Menu `Penetapan` sekarang tersedia khusus untuk user Admin KPI / SDM pada desktop dan mobile.
- Admin dapat memilih jalur Staf Pemasaran/Bisnis atau Staf Operasional/Non-bisnis.
- Daftar penetapan hanya mengambil indikator state Aktif dari katalog dummy.
- Target dan bobot dapat disesuaikan per indikator sebelum masuk preview self-assignment.
- Preview menampilkan paket KPI yang akan dilihat staf, total bobot, dan status kesiapan penetapan.
- Tombol `Bagi bobot rata` membantu menghasilkan total 100% pada skenario demo.
- `Simpan penetapan dummy` hanya menyimpan state frontend dan tidak mengubah kontrak self-assignment atau backend.
- Validasi penetapan menolak daftar kosong, target/bobot tidak valid, dan total bobot selain 100%.
- Mapping penetapan dummy sekarang disimpan di state frontend per jalur, sehingga hasil Admin dapat dibaca oleh layar staf tanpa endpoint baru.
- Jalur Pemasaran/Bisnis memiliki tiga KPI Aktif yang sudah dipetakan sebagai demo awal.
- Jalur Operasional/Non-bisnis sengaja dimulai tanpa mapping untuk memperlihatkan empty state yang dapat diisi Admin dari layar Penetapan.
- Self-assignment Staf Cabang hanya mengambil KPI Aktif yang ada di mapping jalurnya; KPI Draft dan indikator jalur lain tidak ikut ditampilkan.
- Target dan bobot pada self-assignment ditampilkan sebagai inherited dan read-only. Staf hanya memilih indikator yang boleh dipakai.
- Empty state menjelaskan bahwa Admin belum menetapkan KPI untuk jalur tersebut dan memberi konteks tindakan berikutnya.
- Tombol simpan pada jalur staf memberi feedback draf frontend tanpa mengirim mapping dummy ke endpoint kontrak.
- Form self-assignment tetap responsif, mempertahankan loading, empty, error, selected, read-only, dan disabled state.
- Step 14 menambahkan penyimpanan draf frontend-only per identitas dan jalur staf.
- Status draf ditampilkan sebagai `DRAFT` dan masih dapat diedit sebelum pengajuan.
- Tombol `Ajukan self-assessment` hanya aktif setelah draf tersimpan dan minimal satu KPI dipilih.
- Setelah diajukan, status berubah menjadi `SUBMITTED`, pilihan KPI terkunci, dan layar memberi feedback menunggu reviewer.
- Record draf/submission dummy disimpan di state browser selama sesi demo dan tidak menambah endpoint backend.
- Submission `SUBMITTED` dari staf sekarang muncul sebagai tugas baru di Inbox Evaluasi Kepala Cabang.
- Tugas dinamis membawa jalur, nama staf, periode, tanggal pengajuan, deadline demo, dan snapshot KPI inherited.
- Reviewer dapat memilih `REVISION` atau `VERIFIED` dengan catatan wajib; keputusan tetap frontend-only.
- Tugas dengan keputusan `VERIFIED` tetap terlihat dengan badge keputusan, sedangkan `REVISION` tetap terlihat dengan status revisi.
- Record submission dan keputusan dipertahankan saat berpindah role selama sesi demo agar alur staf ke reviewer dapat dicoba.
- Status `REVISION` reviewer sekarang terbaca kembali pada layar self-assignment staf.
- Staf dapat melihat catatan reviewer sebelum membuka ulang draf.
- Aksi `Buka ulang draf` mengubah state menjadi DRAFT dan membuka kembali checkbox KPI.
- Setelah draf disimpan dan diajukan ulang, keputusan REVISION sebelumnya dibersihkan dan tugas kembali ke Inbox sebagai SUBMITTED.
- Target dan bobot tetap inherited read-only selama loop revisi; perubahan hanya pada pilihan KPI dan pengajuan ulang.
- Step 17 menambahkan keputusan reviewer `VERIFIED` dan aksi `Finalisasi dan kunci snapshot` sebagai state frontend-only.
- Reviewer tidak dapat mengubah catatan atau keputusan setelah snapshot berstatus `FINAL`; panel final menampilkan waktu finalisasi dan catatan yang tersimpan.
- Halaman `KPI saya` menampilkan snapshot VERIFIED/FINAL dengan periode, jalur, reviewer, waktu keputusan, catatan, target, realisasi, dan skor per indikator.
- Snapshot VERIFIED/FINAL tidak menyediakan input edit; CTA yang tersedia hanya membuka `Riwayat Penilaian`.
- `Riwayat Penilaian` sekarang menambahkan entry dinamis dari self-assessment staf, termasuk status SUBMITTED, REVISION, VERIFIED, atau FINAL serta tanggal submit/final.
- Detail histori tetap read-only dan membawa item snapshot yang sama dengan keputusan reviewer agar jejak penilaian konsisten.
- Finalisasi tidak menambah endpoint atau tabel; state demo tetap berada di browser dan dipertahankan saat berpindah role selama sesi.
- Step 18 menambahkan menu `Audit trail` khusus Admin KPI/SDM pada navigasi desktop dan mobile.
- Audit trail dummy mencatat kejadian `VERIFIED` dan `FINAL` dari jalur Pemasaran/Bisnis serta Operasional/Non-bisnis.
- Setiap kejadian menampilkan pegawai, jabatan, jalur, periode, aktor, role aktor, waktu keputusan, dan catatan.
- Filter audit trail tersedia berdasarkan jalur dan status keputusan.
- Detail read-only menampilkan snapshot indikator saat keputusan: target, realisasi, dan skor demo.
- Submission reviewer yang diverifikasi atau difinalisasi otomatis membentuk entry audit trail dinamis selama sesi demo.
- Audit trail tidak menyediakan tombol edit atau finalisasi ulang; workflow berwenang tetap berada di Inbox Evaluasi dan KPI saya.
- Step ini tidak menambah endpoint atau tabel backend baru.
- Step 19 menambahkan kolom aksi `Lihat audit` pada tabel Monitoring Admin KPI.
- Aksi Monitoring membuka Audit trail dengan konteks pegawai, jalur, periode, dan status monitoring yang dipilih.
- Jika belum ada keputusan VERIFIED/FINAL untuk record tersebut, Audit trail menampilkan empty state khusus konteks tanpa mengarang kejadian baru.
- Jika tersedia, detail audit langsung memilih kejadian yang sesuai dan tetap menampilkan snapshot read-only.
- Tombol `Lihat semua audit` menghapus konteks fokus agar Admin dapat kembali ke daftar audit global.
- Step 20 menambahkan selector periode bersama pada Monitoring Admin KPI dan Audit trail.
- Default filter periode adalah `Triwulan IV 2026` sebagai periode aktif demo.
- Selector menyediakan `Semua periode`, Triwulan III 2026, Triwulan IV 2026, dan Semester I 2027 dari data periode dummy.
- Ringkasan monitoring, distribusi status, breakdown jalur, tabel perhatian, jumlah audit, dan daftar audit mengikuti periode yang dipilih.
- Header kedua layar menampilkan nama dan rentang tanggal periode terpilih agar konteks tidak hilang saat berpindah halaman.
- Aksi `Lihat audit` dari Monitoring menyamakan filter periode sebelum membuka detail audit.
- Step ini tetap frontend-only dan tidak menambah endpoint atau tabel backend baru.
- Step 21 menambahkan menu `Banding periode` khusus Admin KPI/SDM pada navigasi desktop dan mobile.
- Layar perbandingan menampilkan dua selector periode dummy: Periode A dan Periode B.
- Default perbandingan adalah Triwulan III 2026 versus Triwulan IV 2026.
- Setiap kartu periode menampilkan rentang tanggal, status periode, total kontrak, distribusi DRAFT/SUBMITTED/REVISION/VERIFIED/FINAL, dan jumlah Final per jalur.
- Ringkasan pergeseran menampilkan delta total kontrak, total Final, Final Pemasaran, dan Final Operasional dari Periode A ke Periode B.
- Periode yang belum memiliki snapshot perbandingan menampilkan empty state, bukan angka sintetis tambahan.
- Jika Admin memilih periode yang sama, UI menampilkan validasi agar dua sisi perbandingan berbeda.
- Step ini memakai data dummy frontend dan tidak menambah endpoint atau tabel backend baru.
- Step 22 menambahkan aksi `Lihat kontrak` pada setiap status DRAFT, SUBMITTED, REVISION, VERIFIED, dan FINAL di kartu perbandingan.
- Aksi status membawa periode dan status ke Monitoring Admin KPI sebagai filter aktif.
- Aksi `Final per jalur` juga membawa filter jalur Pemasaran/Bisnis atau Operasional/Non-bisnis.
- Monitoring menampilkan banner konteks `KONTEKS DARI PERBANDINGAN` agar asal filter tetap terbaca.
- Tombol `Lihat semua monitoring` menghapus konteks drill-down tanpa mengubah data.
- Filter Monitoring sekarang dapat memilih status FINAL saat dibuka dari perbandingan, sementara tampilan default tetap fokus pada status aktif.
- Data dummy Monitoring ditambah satu contoh kontrak Final agar jalur drill-down Final dapat dipreview.
- Step ini tetap frontend-only dan tidak menambah endpoint atau tabel backend baru.
- Step 23 menegaskan aksi `Lihat audit` pada setiap baris kontrak hasil drill-down Monitoring.
- Aksi baris membawa pegawai, periode, jalur, dan status kontrak ke Audit trail.
- Audit trail membedakan konteks `KONTEKS DARI DRILL-DOWN` dan `KONTEKS DARI MONITORING` agar asal navigasi terbaca.
- Baris kontrak memiliki accessible label `Lihat audit {nama pegawai}` tanpa mengubah mode read-only.
- Jika keputusan audit belum tersedia, Audit trail tetap menampilkan empty state untuk kontrak dan filter yang dipilih.
- Tombol `Lihat semua audit` menghapus konteks baris dan kembali ke daftar audit global.
- Step 24 menambahkan ringkasan kontrak read-only pada Audit trail untuk kontrak terpilih dari Monitoring atau drill-down perbandingan.
- Ringkasan menampilkan pegawai, jabatan, status, jalur, periode, skor demo, reviewer, tanggal submit, dan tanggal final.
- Summary memakai data snapshot statis atau state submission reviewer yang sedang aktif di browser; tidak ada kontrol edit pada ringkasan.
- Jika konteks kontrak belum memiliki kejadian VERIFIED/FINAL, ringkasan tetap terlihat dan daftar audit mempertahankan empty state yang jujur.
- Catatan read-only menegaskan bahwa perubahan tetap dilakukan melalui workflow yang berwenang.
- Step 25 menambahkan menu `Master jabatan` khusus Admin KPI/SDM.
- Master jabatan dummy memuat Staf Pemasaran, Staf Operasional, Kepala Bidang Pemasaran, Kepala Bidang Operasional, dan Kepala Cabang.
- Selector jabatan dan daftar jabatan membuat konteks organisasi cepat dipilih tanpa menambah daftar user atau cabang baru.
- Detail jabatan menampilkan level, organisasi, cakupan kerja, jalur Pemasaran/Operasional, serta status mapping.
- Mapping KPI read-only hanya menampilkan indikator katalog berstatus Aktif beserta target, bobot, unit, sumber data, dan total bobot.
- Kepala Cabang memakai contoh mapping lintas jalur; Staf Operasional sengaja empty agar state belum tersedia tetap terlihat.
- Pada baseline Step25, detail jabatan tetap read-only; aksi CRUD sekarang berada di editor Step26 agar mode baca tidak bercampur dengan mode perubahan.
- Layar responsif menumpuk daftar jabatan dan detail pada mobile, dengan selector tetap memiliki label dan focus state.
- Perbaikan klik Step 25: menu `Jabatan` tersedia di navigasi mobile dan setiap baris jabatan memakai tombol native dengan `aria-label` detail yang jelas.
- Klik menu atau baris jabatan sekarang mengganti `selectedPositionId` lalu menampilkan detail organisasi dan mapping KPI yang sesuai.
- Step 26 menambahkan tombol `Tambah jabatan` untuk membuka editor create frontend-only.
- Editor menyediakan nama jabatan, level, organisasi, cakupan kerja, jalur mapping, status Aktif/Nonaktif, dan pilihan indikator Aktif.
- Target dan bobot mapping dapat diisi pada editor; validasi menolak field wajib kosong, indikator lintas jalur, target/bobot tidak valid, dan total bobot selain 100%.
- Editor boleh menyimpan jabatan tanpa indikator agar empty state mapping tetap dapat dipreview.
- Tombol `Edit jabatan` membuka state update dengan data jabatan terpilih dan mempertahankan mapping yang ada.
- Tombol `Nonaktifkan` memakai konfirmasi eksplisit; data tidak dihapus dan dapat diaktifkan kembali melalui tombol `Aktifkan`.
- Data create, update, aktif/nonaktif, dan mapping hanya hidup di state browser; tidak ada endpoint baru.
- Saat berpindah menu, editor dan konfirmasi ditutup agar state CRUD tidak tertinggal di halaman lain.
- Step 27 menghubungkan jabatan Staf Pemasaran dan Staf Operasional yang Aktif ke self-assignment secara frontend-only.
- Self-assignment staf hanya membaca indikator Aktif dari mapping jabatan yang sedang aktif; indikator Draft atau nonaktif tidak ikut diwariskan.
- Target dan bobot pada kartu KPI berasal dari mapping jabatan dan tetap read-only di sisi staf.
- Konteks self-assignment menampilkan nama jabatan, unit organisasi, jalur kerja, dan status mapping agar sumber penetapan mudah diaudit.
- Mapping jabatan tanpa indikator Aktif menampilkan empty state yang menjelaskan tindakan Admin berikutnya; jabatan nonaktif menampilkan state peringatan dan tidak membuka KPI.
- Setelah Admin menyimpan perubahan mapping atau mengaktifkan kembali jabatan, katalog self-assignment memuat ulang pada pembukaan berikutnya untuk memperlihatkan preview perubahan.
- Editor Admin memberi catatan bahwa preview self-assignment berubah setelah mapping disimpan; tidak ada preview palsu sebelum commit state demo.
- State pencarian kosong, pilihan aktif, error, dan tombol disabled tetap tersedia.
- Layout mobile dibuat bertumpuk dengan jarak dan ukuran tombol yang tetap nyaman disentuh.
- Warna teks bantuan pada layar masuk diperkuat setelah pengecekan kontras.
- Pointer, active state, disabled state, dan focus state ditambahkan atau diperjelas pada kontrol yang disentuh di step ini.

## Berkas yang berubah

- `frontend/src/routes/+page.svelte`
- `frontend/src/routes/page.css`
- `frontend/vite.config.ts`
- `backend/app/Http/Controllers/Api/V1/DemoIdentityController.php`
- `status.md`

## Verifikasi

- `npm.cmd run check`: lulus.
- `npm.cmd run build`: lulus.
- `npm.cmd run check`: lulus setelah Inbox evaluasi ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah Inbox evaluasi ditambahkan.
- `npm.cmd run check`: lulus setelah detail review dan keputusan ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah detail review dan keputusan ditambahkan.
- `npm.cmd run check`: lulus setelah Monitoring Admin KPI ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah Monitoring Admin KPI ditambahkan.
- `npm.cmd run check`: lulus setelah Katalog KPI dan target Admin ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah Katalog KPI dan target Admin ditambahkan.
- `npm.cmd run check`: lulus setelah Penetapan KPI ke jabatan ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah Penetapan KPI ke jabatan ditambahkan.
- `npm.cmd run check`: lulus setelah self-assignment berbasis penetapan ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah self-assignment berbasis penetapan ditambahkan.
- `npm.cmd run check`: lulus setelah transisi DRAFT/SUBMITTED ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah transisi DRAFT/SUBMITTED ditambahkan.
- `npm.cmd run check`: lulus setelah Inbox menerima submission SUBMITTED dinamis, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah Inbox menerima submission SUBMITTED dinamis.
- `npm.cmd run check`: lulus setelah loop REVISION kembali ke staf ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah loop REVISION kembali ke staf ditambahkan.
- `npm.cmd run check`: lulus setelah state VERIFIED/FINAL dan snapshot read-only ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah state VERIFIED/FINAL dan snapshot read-only ditambahkan.
- `npm.cmd run check`: lulus setelah Audit trail Admin KPI/SDM ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah Audit trail Admin KPI/SDM ditambahkan.
- `npm.cmd run check`: lulus setelah aksi `Lihat audit` dari Monitoring ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah aksi `Lihat audit` dari Monitoring ditambahkan.
- `npm.cmd run check`: lulus setelah filter periode lintas Monitoring dan Audit trail ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah filter periode lintas Monitoring dan Audit trail ditambahkan.
- `npm.cmd run check`: lulus setelah ringkasan perbandingan antar-periode ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah ringkasan perbandingan antar-periode ditambahkan.
- `npm.cmd run check`: lulus setelah drill-down status perbandingan ke Monitoring ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah drill-down status perbandingan ke Monitoring ditambahkan.
- `npm.cmd run check`: lulus setelah aksi `Lihat audit` pada baris hasil drill-down ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah aksi `Lihat audit` pada baris hasil drill-down ditambahkan.
- `npm.cmd run check`: lulus setelah ringkasan kontrak read-only ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah retry; percobaan pertama sempat terkena `memory allocation ... failed`, tetapi build berikutnya selesai normal.
- `npm.cmd run check`: lulus setelah master jabatan dan mapping KPI read-only ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah master jabatan dan mapping KPI read-only ditambahkan.
- `npm.cmd run check`: lulus setelah perbaikan akses klik menu mobile dan baris jabatan.
- `npm.cmd run build`: lulus setelah perbaikan akses klik menu mobile dan baris jabatan.
- `npm.cmd run check`: lulus setelah CRUD master jabatan dan mapping KPI ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: artefak SSR/client `.svelte-kit` berhasil dibuat setelah retry pada environment demo.
- `npm.cmd run check`: lulus setelah inheritance mapping jabatan ke self-assignment ditambahkan, tanpa error atau warning Svelte.
- `npm.cmd run build`: lulus setelah state mapping Aktif, empty, nonaktif, dan preview simpan Admin ditambahkan.
- Frontend lokal dan proxy API merespons `200` setelah step 16 ditambahkan.
- Frontend lokal merespons `200` setelah Inbox evaluasi reviewer ditambahkan.
- Frontend lokal merespons `200` setelah self-assessment bertahap ditambahkan.
- Frontend lokal merespons `200` setelah halaman KPI saya ditambahkan.
- Frontend lokal merespons `200` setelah Dashboard saya ditambahkan.
- Frontend lokal merespons `200` setelah layar Riwayat Penilaian ditambahkan.
- Katalog API Pemasaran mengembalikan 6 indikator demo jalur bisnis.
- Katalog API Operasional mengembalikan 6 indikator demo jalur non-bisnis.
- Frontend lokal merespons `200` setelah layar Admin KPI ditambahkan.
- Frontend lokal merespons `200` setelah Monitoring Admin KPI ditambahkan.
- Frontend lokal merespons `200` setelah Katalog KPI dan target Admin ditambahkan.
- Frontend lokal merespons `200` setelah Penetapan KPI ke jabatan ditambahkan.
- Frontend lokal merespons `200` setelah snapshot VERIFIED/FINAL ditambahkan.
- Frontend lokal merespons `200` setelah Audit trail Admin KPI/SDM ditambahkan.
- Frontend lokal merespons `200` setelah Monitoring terhubung ke Audit trail.
- Frontend lokal merespons `200` setelah filter periode lintas Monitoring dan Audit trail ditambahkan.
- Frontend lokal merespons `200` setelah layar perbandingan antar-periode ditambahkan.
- Frontend lokal merespons `200` setelah drill-down perbandingan ke Monitoring ditambahkan.
- Frontend lokal merespons `200` setelah drill-down baris kontrak ke Audit trail ditambahkan.
- Frontend lokal merespons `200` setelah ringkasan kontrak read-only ditambahkan.
- Frontend lokal merespons `200` setelah master jabatan dan mapping KPI read-only ditambahkan.
- Frontend lokal merespons `200` setelah perbaikan klik Master jabatan.
- Frontend lokal merespons `200` setelah CRUD master jabatan dan mapping KPI ditambahkan.
- Frontend lokal merespons `200` setelah inheritance mapping jabatan ke self-assignment ditambahkan.
- Filter API identitas demo mengembalikan 10 user skenario terpilih.
- Login API dengan salah satu user skenario tetap merespons `200` melalui proxy frontend.
- Pengecekan kontras warna layar masuk: lulus untuk pasangan teks utama dan teks konteks baru.
- Pengecekan kontras warna note inherited dan field read-only: lulus untuk pasangan teks baru.
- Server frontend lokal merespons `200` di `http://127.0.0.1:5180`.
- Backend demo dijalankan memakai PHP 8.4.27 dari Laravel Herd pada `http://127.0.0.1:8100`.
- Endpoint identitas, CSRF, login dummy Pemasaran, dan siklus OPEN merespons `200` saat diuji dengan origin frontend.
- Click-through browser Step 27 belum dicatat karena Edge headless diblokir execution policy; state mapping Aktif, target/bobot read-only, empty mapping, jabatan nonaktif, reload setelah simpan, label form, focus state, hasil check/build, dan respons HTTP lokal diverifikasi dari code path.

## Port demo lokal

- Frontend KPI: `http://127.0.0.1:5180`
- Backend KPI: `http://127.0.0.1:8100`
- Port `5174` dan `8001` dipakai project lain di mesin yang sama.
- Endpoint `GET /api/v1/demo/identities` melalui frontend merespons `200`.
- Sesi dummy `DEMO-KASUBBID-001` melalui proxy merespons `200` setelah backend dipindahkan ke port 8100.

## Perbaikan login demo

Masalah login berasal dari port API yang tertukar dengan project lain. Frontend KPI sebelumnya meneruskan request ke port 8000, sedangkan port itu tidak lagi menunjuk ke backend KPI. Proxy frontend sekarang membaca `VITE_API_PROXY_TARGET` dan default ke port 8100.

Sesi dummy sudah diverifikasi melalui alur CSRF dan `POST /api/v1/demo/session` dengan respons `200` untuk `DEMO-KASUBBID-001`.

## Rekomendasi step berikutnya

Bangun preview dampak perubahan mapping KPI per jabatan. Fokus: Admin dapat melihat indikator yang bertambah/berkurang dan perubahan target/bobot sebelum mapping dipakai self-assignment.

## Prompt lanjutan

Lanjutkan UI/UX step 28 dari PRD.md: buat preview dampak perubahan mapping KPI per jabatan untuk Admin KPI/SDM. Tampilkan jabatan terdampak, indikator yang ditambah/dihapus, perubahan target/bobot, dan warning bila periode sudah OPEN. Frontend-only, tanpa backend baru, fokus prototipe responsif, lalu update status.md.

## Urutan setelah step berikutnya

1. Preview dampak perubahan mapping KPI per jabatan.
