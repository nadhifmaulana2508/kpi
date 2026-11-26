# Kebutuhan API SSO dan SIMPEG untuk KPI Perbankan

Dokumen ini bisa diteruskan ke tim SSO/SIMPEG Bank. Nama dan path endpoint di bawah hanya contoh; endpoint yang sudah tersedia boleh digunakan selama menyediakan fungsi dan data yang setara.

## Lingkup integrasi

Aplikasi KPI memerlukan dua hal:

1. Login SSO pegawai dan profil pegawai yang sudah terverifikasi.
2. Data organisasi SIMPEG untuk memetakan jabatan, unit, pemegang jabatan aktif, dan hirarki atasan.

Base URL development yang disampaikan: https://devsso.bkkjateng.co.id

Kredensial aplikasi seperti APP dan X-App-Token disimpan di backend aplikasi. Token pegawai hasil login hanya dipakai untuk sesi pegawai dan mengambil profil; jangan dicatat di dokumen atau repository.

## 1. Login SSO otomatis

Endpoint yang sudah dicoba:

- POST /api/auth/login menerima id_peg dan password, lalu mengembalikan token.
- GET /api/auth/whoami menerima token Bearer dan mengembalikan profil pegawai.

Endpoint login tersebut bisa dipakai untuk pengujian API, tetapi masih meminta aplikasi menerima password pegawai. Untuk login SSO otomatis dari browser, mohon informasikan atau sediakan salah satu mekanisme berikut:

### Pilihan yang disarankan: OpenID Connect

Mohon berikan alamat issuer/discovery, biasanya tersedia di:

- /.well-known/openid-configuration

Aplikasi memerlukan authorization endpoint, token endpoint, JWKS/public key untuk validasi token, dan userinfo endpoint atau klaim identitas pada ID token. Logout endpoint boleh disediakan bila didukung.

Tim bank juga perlu mendaftarkan callback URL aplikasi dan memberikan client_id, scope, serta metode autentikasi client yang digunakan. Alur harus mendukung state dan nonce; PKCE disarankan.

### Jika SSO menggunakan API khusus

Mohon berikan:

- URL untuk mengarahkan pegawai ke halaman login SSO.
- Callback URL yang perlu didaftarkan.
- Endpoint backend untuk menukar authorization code sekali pakai menjadi token.
- Cara memvalidasi token serta masa berlakunya.
- Endpoint profil seperti whoami/userinfo yang menerima token Bearer.

Jangan meminta aplikasi menyimpan password SSO pegawai di browser atau database KPI.

## 2. Data profil dari whoami/userinfo

Profil harus mengembalikan employee_id yang stabil dan sama dengan id_peg di SIMPEG. Field lain yang diperlukan untuk mengisi identitas KPI:

| Field | Kegunaan |
| --- | --- |
| employee_id | Kunci pemetaan akun SSO ke pegawai KPI; wajib dan unik |
| full_name | Nama pegawai |
| job_position | Nama jabatan aktif |
| unit_kerja | Nama unit aktif |
| branch_name | Kantor/cabang/area penempatan |
| level | Tingkat jabatan |
| group_jabatan | Kelompok jabatan |
| email | Opsional |
| kode | Opsional, bila merupakan kode unit/organisasi yang stabil |

Jika tersedia, sertakan juga kode jabatan dan kode unit agar aplikasi tidak perlu mencocokkan nama teks. Contoh endpoint saat ini mengembalikan employee_id, full_name, email, telp, branch_name, unit_kerja, job_position, level, dan group_jabatan.

## 3. Direktori organisasi SIMPEG

Aplikasi perlu menampilkan seluruh jabatan di Kantor Pusat, Kantor Wilayah, dan Kantor Cabang, termasuk jabatan yang belum memiliki pegawai. Data penempatan dipakai untuk mengetahui siapa yang saat ini mengisi jabatan tersebut.

Path berikut merupakan contoh. Empat resource boleh disediakan terpisah atau digabung menjadi satu endpoint sinkronisasi organisasi.

### A. Master jabatan

Contoh: GET /api/simpeg/jabatan

Field minimal:

- kode_jabatan
- nama_jabatan
- nama_unit_kerja
- level
- lingkup
- group_jabatan
- kuota
- parent_kode_jabatan

parent_kode_jabatan harus menunjuk ke kode jabatan atasan langsung sesuai struktur SIMPEG. Sertakan semua jabatan, termasuk yang kosong. Kode jabatan harus stabil.

### B. Master pegawai

Contoh: GET /api/simpeg/pegawai

Field minimal:

- id_peg, dengan nilai yang sama dengan employee_id dari SSO
- nama
- status_pegawai

Email boleh disertakan bila memang diperlukan.

### C. Penempatan jabatan

Contoh: GET /api/simpeg/penempatan

Field minimal:

- id_jab
- id_peg
- kode_jabatan
- kode_unit atau unit_kerja
- tmt_jabatan
- sampai_tgl
- status_jab

Sertakan no_sk bila boleh dibagikan. Data harus menunjukkan pemegang jabatan aktif pada setiap unit dan tetap menyediakan tanggal efektif untuk membedakan penempatan aktif dari riwayat.

### D. Master unit kerja

Contoh: GET /api/simpeg/unit-kerja

Field minimal:

- kode_unit
- nama_unit
- jenis_unit, misalnya PUSAT, KANWIL, atau KC
- parent_kode_unit

Struktur parent unit diperlukan karena jabatan yang sama dapat muncul di banyak cabang atau wilayah.

### Hirarki atasan untuk alur penilaian

Untuk menentukan penilai pegawai, aplikasi perlu menghubungkan jabatan pegawai dengan parent_kode_jabatan, lalu mencari pemegang jabatan aktif di unit yang benar. Karena satu kode jabatan dapat ditempati di banyak unit, setiap penempatan wajib berisi kode_unit yang konsisten dengan struktur unit.

Jika API SIMPEG dapat langsung mengembalikan rantai atasan pegawai, mohon sertakan daftar berurutan berisi employee_id, nama, kode_jabatan, nama_jabatan, group_jabatan, level, kode_unit, dan unit_kerja untuk setiap atasan yang sedang aktif. Ini dibutuhkan karena jalur atasan aktual bisa melintasi unit, misalnya Kepala Cabang ke Kepala Kantor Wilayah.

## 4. Aturan alur penilai saat ini

Untuk tahap awal, alur penilaian jabatan non-PE mengikuti aturan berikut. KPI indikator tetap dipetakan berdasarkan kode jabatan; aturan di bawah menentukan siapa yang menilai.

| Jabatan pegawai yang dinilai | Penilai 1 | Penilai 2 |
| --- | --- | --- |
| Staf | Atasan terdekat dengan level Kepala Bidang dan group jabatan PS | Atasan terdekat dengan group jabatan PE |
| Kepala Sub Bidang | Atasan terdekat dengan level Kepala Bidang dan group jabatan PS | Atasan terdekat dengan group jabatan PE |
| Kepala Bidang | Kepala Divisi | Tidak ada |
| Kepala Cabang | Kepala Kantor Wilayah | Tidak ada |
| Kepala Kantor Wilayah | Jajaran Direksi | Tidak ada |
| Kepala Divisi | Jajaran Direksi | Tidak ada |

Penilaian pegawai yang jabatannya termasuk kelompok PE belum masuk tahap implementasi ini. PE tetap bisa menjadi penilai untuk jabatan di bawahnya sesuai tabel.

Agar alur tersebut bisa ditentukan dengan benar, API perlu memberikan rantai atasan aktual beserta pegawai yang sedang mengisi setiap posisi: employee_id, kode_jabatan, nama_jabatan, group_jabatan, level, kode_unit, dan nama unit. Jabatan antara yang tidak termasuk penilai pada tabel harus dapat dilewati. Jika penilai yang diwajibkan tidak memiliki pemegang aktif, kirimkan kondisi kosong; aplikasi akan menandainya untuk tindak lanjut admin, bukan memilih orang secara diam-diam.

parent_kode_jabatan saja belum cukup untuk memastikan Kepala Cabang dinilai Kepala Kantor Wilayah. Pada contoh data master, jabatan Kepala Cabang dan Kepala Kantor Wilayah sama-sama dapat memiliki parent Direktur Utama. Mohon API menyediakan relasi pelaporan aktual yang mempertimbangkan penempatan dan hirarki unit, atau langsung mengembalikan rantai atasan aktif per pegawai.

## 5. Ketentuan teknis

- Pertahankan semua ID dan kode sebagai string agar format dan nol di depan tidak hilang.
- Sediakan pagination untuk daftar besar.
- Sediakan filter perubahan, misalnya updated_since, untuk sinkronisasi berkala.
- Sertakan waktu pembaruan data, misalnya updated_at.
- Jelaskan autentikasi server-to-server, masa berlaku token, batas request, dan format error.
- Untuk sinkronisasi direktori, gunakan kredensial aplikasi/server, bukan password pegawai.
- Respons boleh memakai format API yang sudah ada, misalnya status, message, dan data.
- API direktori bersifat read-only untuk aplikasi KPI.

## Bentuk data direktori yang diharapkan

Nama properti boleh disesuaikan dengan standar API SIMPEG, selama maknanya sama.

    {
      "status": 200,
      "message": "OK",
      "data": {
        "updated_at": "2026-11-23T10:00:00+07:00",
        "positions": [
          {
            "kode_jabatan": "79",
            "nama_jabatan": "Kepala Kantor Wilayah",
            "nama_unit_kerja": "Area Kantor Wilayah",
            "level": "Kepala Kantor Wilayah",
            "lingkup": "KANWIL",
            "group_jabatan": "PE",
            "kuota": 4,
            "parent_kode_jabatan": "4"
          }
        ],
        "employees": [
          {
            "id_peg": "ID-PEGAWAI",
            "nama": "Nama Pegawai",
            "status_pegawai": "Aktif"
          }
        ],
        "assignments": [
          {
            "id_jab": "ID-PENEMPATAN",
            "id_peg": "ID-PEGAWAI",
            "kode_jabatan": "79",
            "kode_unit": "KODE-UNIT",
            "tmt_jabatan": "2026-01-01",
            "sampai_tgl": null,
            "status_jab": "Aktif"
          }
        ],
        "units": [
          {
            "kode_unit": "KODE-UNIT",
            "nama_unit": "Nama Unit",
            "jenis_unit": "KANWIL",
            "parent_kode_unit": null
          }
        ]
      }
    }

## Pesan siap kirim ke tim bank

> Kami sedang mengintegrasikan SSO dan SIMPEG ke aplikasi KPI Perbankan. Mohon informasikan mekanisme SSO browser yang tersedia, idealnya OpenID Connect, termasuk discovery/issuer, endpoint authorization dan token, validasi token, userinfo/whoami, callback URL yang perlu didaftarkan, scope/claim, serta proses logout bila tersedia. Endpoint login POST /api/auth/login dengan id_peg dan password yang saat ini tersedia dapat digunakan untuk pengujian, tetapi kami juga memerlukan alur redirect SSO agar pegawai tidak perlu menyerahkan password SSO ke aplikasi KPI.
>
> Untuk sinkronisasi SIMPEG, mohon sediakan data seluruh master jabatan beserta parent_kode_jabatan, master pegawai aktif, penempatan jabatan dengan id_peg/kode_jabatan/kode_unit/tanggal/status, dan master unit beserta parent_kode_unit. Data jabatan harus mencakup posisi kosong; penempatan harus menunjukkan pemangku aktif per unit. employee_id dari SSO harus cocok dengan id_peg SIMPEG. Path endpoint boleh mengikuti standar yang sudah ada; empat resource terpisah atau satu endpoint gabungan sama-sama dapat digunakan. Mohon sertakan autentikasi server-to-server, pagination, filter perubahan, waktu pembaruan, format error, dan contoh respons. Mohon sertakan rantai atasan aktif per pegawai yang mempertimbangkan unit penempatan. Alur awal KPI: Staf/Kepala Sub Bidang dinilai oleh Kepala Bidang (PS), lalu PE; Kepala Bidang dinilai Kepala Divisi; Kepala Cabang dinilai Kepala Kantor Wilayah; Kepala Kantor Wilayah dan Kepala Divisi dinilai Jajaran Direksi. Penilaian pegawai kelompok PE menyusul.
