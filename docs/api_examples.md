# Contoh API untuk alur demo KPI

Semua ID dan isi di dokumen ini merupakan data sintetis dari seed lokal. Contoh ini menunjukkan alur susun KPI sendiri, pengisian kontrak, dan tinjauan. Pengajuan kontrak baru menunggu implementasi reviewer routing. Nilai KPI hanya untuk mencoba aplikasi, bukan kebijakan penilaian bank.

## Sesi lokal dan token CSRF

Minta cookie CSRF sebelum request yang mengubah data:

```http
GET http://localhost:8000/sanctum/csrf-cookie
```

Biarkan browser mengirim cookie sesi dan cookie `XSRF-TOKEN`. Untuk setiap `POST`, `PATCH`, dan `DELETE`, kirim header `X-XSRF-TOKEN` berisi nilai cookie `XSRF-TOKEN` setelah URL decoding. Jangan menyalin nilai cookie ke log atau dokumentasi.

Pilih identitas pemilik kontrak contoh:

```http
POST http://localhost:8000/api/v1/demo/session
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "identity_id": "DEMO-KADIV-001"
}
```

Selector identitas hanya tersedia pada lingkungan lokal. API mengembalikan identitas aktif di dalam envelope `data`.

## Susun KPI untuk diri sendiri

Pilih salah satu identitas staf/Kepala Sub Bidang yang memiliki `self_assignment_available: true`, misalnya `DEMO-OPERASIONAL-001`. Siklus aktif ber-ID dinamis `DEMO-CYCLE-YYYYMM`; ambil ID bulan berjalan dari:

```http
GET http://localhost:8000/api/v1/cycles
```

Ambil katalog untuk jabatan aktif menggunakan `position_id` dari `GET /api/v1/me`:

```http
GET http://localhost:8000/api/v1/kpi-definitions?position_id=<position_id>&per_page=100
```

Buat kontrak dengan memilih KPI dari katalog dan mengirim target. Pemilik kontrak dan jabatan selalu diambil dari sesi aktif. Bobot tidak dikirim; server menormalisasi bobot katalog untuk KPI yang dipilih sampai total 100%.

```http
POST http://localhost:8000/api/v1/contracts
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "cycle_id": "<ID siklus bulanan terbuka>",
  "items": [
    {"kpi_definition_id": "DEMO-KPI-OPERASIONAL-01", "target": 100},
    {"kpi_definition_id": "DEMO-KPI-OPERASIONAL-02", "target": 50}
  ]
}
```

Respons `201` membuat kontrak `DRAFT`. Satu identitas hanya dapat membuat satu kontrak per siklus. Target KPI tipe `Z` harus `0`; tipe `Q`/`L` menggunakan skala maksimal `100`. Kontrak belum dapat diajukan sampai reviewer routing diterapkan.

## Cari dan isi kontrak

Setelah seed demo dijalankan, kontrak contoh `DEMO-CONTRACT-0001` berada pada siklus bulanan bulan berjalan, berstatus `DRAFT`, dan reviewer-nya `DEMO-DIR-001`. Ambil ID siklus dari `GET /api/v1/cycles`.

```http
GET http://localhost:8000/api/v1/contracts?employee_id=DEMO-KADIV-001
```

Isi indikator ketiga. Indikator contoh bertipe `Q` dengan target 95; realisasi harus mengikuti skala yang dipakai indikator. Bukti di demo berupa referensi teks, bukan unggahan berkas.

```http
PATCH http://localhost:8000/api/v1/contracts/DEMO-CONTRACT-0001/items/DEMO-CONTRACT-0001-ITEM-03
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "actual": 94.5,
  "note": "Realisasi contoh untuk latihan demo.",
  "evidence": [
    {
      "label": "Rekap sintetis",
      "reference": "DOK-DEMO-2026-001"
    }
  ],
  "state": "VALID"
}
```

Pemilik kontrak dapat mengisi realisasi, catatan, dan bukti. Mengubah target atau bobot memerlukan hak reviewer/SDM serta alasan. Reviewer dapat memindahkan bobot melalui beberapa perubahan item saat kontrak masih DRAFT atau REVISION. Total bobot wajib kembali tepat 100% sebelum pengajuan; selama belum seimbang, skor total demo belum tersedia. Ulangi pengisian untuk indikator lain sebelum mengajukan kontrak.

Tambahkan komentar pada kontrak:

```http
POST http://localhost:8000/api/v1/contracts/DEMO-CONTRACT-0001/comments
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "item_id": "DEMO-CONTRACT-0001-ITEM-03",
  "message": "Referensi bukti ini hanya contoh sintetis."
}
```

## Ajukan, tinjau, dan finalisasi

Ajukan setelah seluruh indikator lengkap, total bobot 100%, bobot data valid memenuhi batas demo, dan reviewer aktif tersedia:

```http
POST http://localhost:8000/api/v1/contracts/DEMO-CONTRACT-0001/submit
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>
```

Ganti identitas demo ke reviewer yang tersimpan pada kontrak, lalu setujui atau minta revisi:

```http
POST http://localhost:8000/api/v1/demo/session
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "identity_id": "DEMO-DIR-001"
}
```

```http
POST http://localhost:8000/api/v1/contracts/DEMO-CONTRACT-0001/reviews
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "decision": "VERIFIED",
  "comment": "Pemeriksaan demo selesai."
}
```

Setelah status `VERIFIED`, ganti identitas ke SDM demo dan finalisasi:

```http
POST http://localhost:8000/api/v1/demo/session
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "identity_id": "DEMO-SDM-001"
}
```

```http
POST http://localhost:8000/api/v1/contracts/DEMO-CONTRACT-0001/reviews
Content-Type: application/json
X-XSRF-TOKEN: <nilai-cookie-XSRF-TOKEN-yang-sudah-di-decode>

{
  "decision": "FINAL",
  "comment": "Finalisasi untuk skenario demo."
}
```

Riwayat tindakan kontrak dapat dibaca setelah kembali ke salah satu identitas yang berwenang:

```http
GET http://localhost:8000/api/v1/audit-events?contract_id=DEMO-CONTRACT-0001&per_page=50
```

## Respons kesalahan

API membungkus kesalahan dengan bentuk yang sama, termasuk validasi `422`:

```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Data yang dikirim belum memenuhi aturan validasi.",
    "details": {
      "actual": [
        "<pesan validasi untuk field actual>"
      ]
    }
  }
}
```

Untuk status dan skema lengkap, lihat [kontrak OpenAPI](openapi.yaml). Contoh ini memakai ID seed yang stabil; reset data demo dapat mengembalikan kontrak ke kondisi awal.
