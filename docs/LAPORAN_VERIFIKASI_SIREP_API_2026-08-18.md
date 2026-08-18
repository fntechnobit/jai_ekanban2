# Laporan Verifikasi Integrasi API SIREP — jai_ekanban2

**Tanggal uji:** 2026-08-18
**Commit yang diuji:** `02dbe46` (*feat: integrasi listing dari API SIREP dengan fallback ke DB lama*)
**Lingkungan:** XAMPP Windows Server 2019, PHP 8.4.16, MariaDB 10.4.32, Laravel 12.51.0
**Deploy path:** `c:\xampp\htdocs\ekanban2` → `http://localhost/ekanban2/public`
**Database:** `ekanban2` (clone penuh dari `ekanban`, 40 tabel, terverifikasi `CHECKSUM TABLE` identik)

> Dokumen ini ditulis agar dapat dianalisa ulang oleh agent AI lain. Bagian
> **Fakta Terukur** hanya berisi hasil observasi langsung. Bagian **Temuan** berisi
> kesimpulan beserta bukti kodenya. Bagian **Belum Terjawab** berisi hal yang
> sengaja TIDAK diputuskan karena butuh keputusan domain dari tim PPC.

---

## 1. Ringkasan Eksekutif

| Pertanyaan | Jawaban |
|---|---|
| Apakah sinkronisasi listing benar-benar mengambil dari API SIREP? | **Ya**, terbukti (`source: api`, `listing_stage.source='api'`) |
| Apakah generate jadwal assy berhasil? | **Ya**, 17 jadwal terbentuk dari data API |
| Apakah sudah bisa dipakai untuk seluruh conveyor? | **Belum** — hanya 2 dari 14 conveyor. Lihat Temuan T-1 |
| Apakah `DB_LISTING_*` masih dipakai? | **Tidak** — sudah dihapus dari `.env`, terverifikasi di kode |
| Bila API SIREP mati, apakah proses berhenti tanpa mencari sumber lain? | **Ya**, terverifikasi lewat uji kegagalan. Data tidak berubah sedikit pun. Lihat 3.6 |

Status: **mekanisme berfungsi, cakupan data terblokir oleh bug pemetaan nama conveyor.**

---

## 2. Konfigurasi Saat Diuji

`.env` (nilai relevan):

```
DB_DATABASE=ekanban2
LISTING_SOURCE=api
SIREP_API_BASE_URL=http://10.62.230.51/sirep-backend/public/api/shared
SIREP_API_TOKEN=(tidak diset — API tanpa autentikasi)
SESSION_COOKIE=ekanban2_session
```

`DB_LISTING_*` **sudah dihapus**. Dasar penghapusan (bukan asumsi):

- `mysql_listing` hanya dipakai oleh `App\Models\Listing` (`app/Models/Listing.php:17`).
- `App\Models\Listing` hanya dirujuk di `app/Services/Listing/DbListingSource.php:41`.
- `DbListingSource` hanya di-instantiate bila `config('sirep.listing_source') === 'db'`
  (`app/Providers/AppServiceProvider.php:23-26`).

**Implikasi penting:** frasa "fallback ke DB lama" pada pesan commit adalah **saklar
konfigurasi manual**, BUKAN fallback otomatis saat API mati. Bila API tidak dapat
dihubungi, `ListingSyncService::syncListingData()` berhenti dengan
`success: false` (`app/Services/ListingSyncService.php:69-80`) dan tidak beralih ke DB.

---

## 3. Fakta Terukur

### 3.1 Konektivitas API

`php artisan sirep:check` →

```
Sumber listing aktif : api
Base URL             : http://10.62.230.51/sirep-backend/public/api/shared
1. Uji koneksi
   OK — API SIREP dapat dihubungi
```

Latensi endpoint `/listing` diukur langsung via curl: **±0,52 detik per conveyor**
(5 sampel: 0.522, 0.521, 0.513, 0.539, 0.544 s). API bukan sumber lambat.

### 3.2 Bentuk respons API

`GET /listing?conveyor=B3-EGI&start_date=2026-01-01&end_date=2026-12-31` → array telanjang, 15 baris.

Field yang dikembalikan:

```
id, carline, conveyor, assy_code, assy_number, level, pattern, status_assy,
standard_packing_sea, standard_packing_air, max_pallet_sea, max_pallet_air,
date, quantity, shipping_method, remarks, estimated_arrival_date,
box_start, box_end, seq, is_overtime, is_printing, is_printing_kanban
```

Contoh satu baris:

```json
{"id":1866,"carline":"J42U-EGI","conveyor":"B3-EGI","assy_code":"E074",
 "assy_number":"24011-7YA0A","level":"0351","pattern":"EGI","status_assy":true,
 "standard_packing_sea":2,"max_pallet_sea":16,"date":"2026-07-30","quantity":128,
 "shipping_method":"sea","box_start":1,"box_end":64,"seq":1,"is_overtime":false,
 "is_printing":false,"is_printing_kanban":false}
```

`GET /conveyor` → amplop `{"data":[...]}`, 37 conveyor. Mayoritas
`normal_capacity` dan `overtime_capacity` bernilai `null`; hanya 6 conveyor
yang terisi (C1, AB3, C6, B3-EGI, B3-ENG, AB17 BNV).

**Cakupan tanggal data API (per 2026-08-18):** B3-EGI hanya punya data
2026-07-13 s.d. 2026-07-31. Rentang Agustus kosong untuk conveyor ini.
B3-ENG punya data sampai 2026-08-22.

### 3.3 Sinkronisasi kapasitas conveyor

`php artisan sirep:sync-conveyor --apply` → berhasil, 1 conveyor berubah.

Kondisi DB sesudahnya:

```
id | conveyor | capacity | overtime_capacity | capacity_synced_at
 1 | B3-EGI   |      136 |               160 | 2026-08-18 10:01:21
 2 | B3-ENG   |       78 |                96 | 2026-08-18 10:01:21
```

12 conveyor master lain tidak tersentuh karena namanya tidak ditemukan di SIREP.

Catatan minor (kosmetik, bukan fungsional): pada kolom keterangan muncul string
tergabung tanpa pemisah — `"samaambang over SIREP 96 vs hitungan kami 95"`.
Ada `.` atau spasi yang hilang saat merangkai pesan di
`app/Console/Commands/SirepSyncConveyorCommand.php`.

### 3.4 Sinkronisasi listing

`ListingSyncService::syncListingData('2026-08-18','2026-08-22')` →

```
sumber       : api
success      : true
total_records: 10
inserted     : 0
updated      : 0
deleted      : 0
skipped      : 10
errors       : []
```

12 peringatan muncul, semuanya menunjukkan **pengaman bekerja sesuai desain**:

- 8 peringatan: conveyor `B1-J42U`, `B1-J42U STATIC`, `B1-P33A-J1`,
  `B1-STATIC-P33A`, `B2-LH`, `B2-RH`, `B2-J42U LHD`, `B2-J42U RHD` — API balik
  kosong, staging **tidak dihapus** ("dibiarkan, mohon diperiksa manual").
- 4 peringatan: listing `24012-7YA1A` / `24012-7YA1E` / `24012-7YA1B` pada
  B3-ENG (18–19 Agu) dibatalkan di SIREP tetapi jadwalnya sudah terkunci /
  kanban tercetak → **tidak dihapus**, dilaporkan sebagai peringatan.

### 3.5 Generate jadwal assy (end-to-end)

`AssySchedulerService::generateSchedules('2026-08-18','2026-08-22')` →

```
success     : true
step_failed : -
generated   : 17
sync_detail : {"total_records":10,"synced":10,"skipped":0}
message     : Berhasil membuat 17 schedule.
```

Verifikasi asal-usul lewat join `assy_schedule` → `listing_stage`:

```sql
SELECT ls.source, COUNT(*) jadwal, SUM(a.is_lock) terkunci
FROM assy_schedule a JOIN listing_stage ls ON ls.id = a.listing_id
WHERE a.schedule >= '2026-08-18' AND a.schedule < '2026-08-23'
GROUP BY ls.source;
```

```
source | jadwal | terkunci
api    |     17 |        0     ← hasil generate, murni dari API
db     |    117 |      109     ← data lama, tidak diganggu
```

Seluruh 17 jadwal hasil generate berada di conveyor **B3-ENG** — satu-satunya
conveyor master yang punya data API pada rentang tersebut.

### 3.6 Perilaku saat API SIREP mati (uji kegagalan)

**Persyaratan yang diuji:** bila API SIREP tidak dapat dihubungi, proses harus
berhenti dengan galat dan notifikasi, serta **tidak** mencari sumber data lain —
termasuk database listing lama.

Simulasi dilakukan dengan mengarahkan `sirep.api.base_url` ke host mati
(`http://127.0.0.1:9/...`, port discard → *connection refused*) pada runtime,
lalu me-*forget* instance container agar klien dibangun ulang.

**Uji A — `syncListingData('2026-08-18','2026-08-22')`**

```
SEBELUM: total=42 {"api":10,"db":32}

success : false
source  : api                 ← tidak berpindah ke db
message : Tidak dapat menghubungi API SIREP: cURL error 7: Failed to connect
          to 127.0.0.1 port 9 ... /conveyor
errors  : [ ... ]

SESUDAH: total=42 {"api":10,"db":32}      ← data utuh, tidak ada yang terhapus
```

Penjaga `ping()` (`app/Services/ListingSyncService.php:69-80`) menyala sebelum
pengambilan data apa pun, sehingga tidak ada penulisan parsial.

**Uji B — `generateSchedules('2026-08-18','2026-08-22')`** — ini jalur yang lebih
berisiko, karena `generateSchedules` menghapus `listing_stage` **lebih dulu**
baru menyinkronkan.

```
SEBELUM: listing_stage=42  assy_schedule=134

success     : false
step_failed : sync_listing
generated   : 0
message     : Gagal mengambil data listing terbaru dari API SIREP: ...
              Proses generate dihentikan dan tidak ada sumber cadangan yang dicoba.

SESUDAH: listing_stage=42  assy_schedule=134   ← rollback utuh
```

`DB::rollBack()` terbukti mengembalikan penghapusan staging. Tidak ada jadwal
yang hilang.

**Kesimpulan:** ketiga persyaratan terpenuhi — berhenti dengan galat, notifikasi
sampai ke operator (`ListingSyncController` mengembalikan HTTP 400 + pesan;
frontend menampilkannya di `resources/views/system/listing_sync/index.blade.php:198-207`
dan `resources/views/schedule/assy_scheduler/index.blade.php:250-256`), dan tidak
ada percobaan ke sumber lain.

**Regresi:** setelah perbaikan T-4 di bawah, sinkronisasi terhadap API asli diuji
ulang dan tetap `success: true`, `source: api`, `errors: []`, 10 record.

---

## 4. Temuan

### T-1 (BLOKER) — `sirep_conveyor_code` tidak pernah dibaca oleh jalur listing

**Dampak:** sinkronisasi & generate hanya berfungsi untuk conveyor yang nama
master-nya identik dengan nama di SIREP. Saat ini **2 dari 14** (B3-EGI, B3-ENG).

Migrasi `2026_07_28_000001_add_sirep_fields_for_api_integration` menambahkan
kolom `master_conveyor.sirep_conveyor_code` dengan tujuan eksplisit
(dikutip dari migrasinya sendiri):

> `sirep_conveyor_code` : dipakai bila nama conveyor di SIREP berbeda dengan
> nama master. Kosong = pakai kolom `conveyor`.

Docblock `app/Services/Listing/ApiListingSource.php:101-107` juga menjanjikan hal
yang sama. Namun implementasinya tidak melakukannya:

```php
// app/Services/Listing/ApiListingSource.php:109-121
private function activeConveyorCodes(): array
{
    return MasterConveyor::query()
        ->pluck('conveyor')          // ← sirep_conveyor_code tidak pernah dibaca
        ->map(fn ($name) => trim((string) $name))
        ->filter()->unique()->values()->all();
}
```

`grep -rn "sirep_conveyor_code" app/` hanya menghasilkan 2 kecocokan:
`SirepSyncConveyorCommand.php:52` (sinkron kapasitas) dan komentar di
`ApiListingSource.php:104`. Tidak ada di jalur pengambilan listing.

**Bukti data.** Nama conveyor tidak beririsan antara master dan SIREP:

| Ada di master, TIDAK ada di SIREP | Ada di SIREP, TIDAK ada di master |
|---|---|
| B1-J42U, B1-J42U STATIC, B2-J42U LHD, B2-J42U RHD, B1-P33A-J1, B1-STATIC-P33A, B2-RH, B2-LH | AB9-EXT, AB9, AT16, AT19, AT2, AT6, C5, C5A–C5D, C9, AB5, C4, AT7, B1, AT9, B2, AT11, AB1, AB6, AB8, 12B, 16C, C7, AB1-LHD, AB1-RHD, AB17 BC, AB16 BC, AB9-EXT1, AB8-EXT, C1, AB3, C6, AB17 BNV |

Seluruh baris `master_conveyor.sirep_conveyor_code` saat ini `NULL`.

**Perbaikan yang dibutuhkan ada 3 lapis** — penting, karena memperbaiki lapis 1
saja akan menghasilkan bug diam-diam:

1. `activeConveyorCodes()` mengirim `sirep_conveyor_code` bila terisi, jatuh ke
   `conveyor` bila kosong.
2. **Terjemahan balik** nama SIREP → nama master sebelum menulis
   `listing_stage.conveyor`. Saat ini adapter menyalin nama apa adanya dari
   respons API (`SirepListingAdapter.php:52`), sedangkan generate menjodohkan
   `listing_stage.conveyor` dengan `master_conveyor.conveyor`
   (`AssySchedulerService.php:129` dan `:187`). Tanpa langkah ini, listing akan
   masuk staging tetapi **tidak akan pernah terjadwal** karena lookup master gagal.
3. `sirep_conveyor_code` ditambahkan ke `MasterConveyor::$fillable`
   (`app/Models/MasterConveyor.php:18-28`). Saat ini tidak ada di sana, sehingga
   `create()` / `update()` akan membuangnya diam-diam.

### T-2 (DIKONFIRMASI SEBAGAI PERILAKU YANG DIINGINKAN) — tidak ada fallback otomatis

Bila API SIREP mati, proses berhenti dengan galat + notifikasi dan tidak menoleh
ke sumber mana pun, termasuk database listing lama. Dikonfirmasi oleh pemilik
sistem pada 2026-08-18 sebagai perilaku yang **memang dikehendaki**, dan sudah
diverifikasi empiris pada 3.6. Bukan bug, dan tidak boleh "diperbaiki" menjadi
fallback otomatis oleh pekerjaan berikutnya.

### T-3 (KOSMETIK) — string keterangan tergabung

Lihat 3.3. `"samaambang over SIREP 96..."` — pemisah hilang saat merangkai pesan
di `SirepSyncConveyorCommand.php`. **Belum diperbaiki.**

### T-4 (SUDAH DIPERBAIKI) — pesan galat menyebut sumber yang salah

Saat generate gagal karena API mati, pesan ke operator berbunyi *"Gagal mengambil
data listing terbaru dari **database listing**"*. Ini peninggalan implementasi
berbasis DB dan menyesatkan: operator akan memeriksa sistem yang salah, padahal
yang mati adalah API SIREP. Karena persyaratan T-2 justru menuntut notifikasi
yang jelas, teks ini diperbaiki:

- `app/Services/AssySchedulerService.php` — sebutan sumber kini diturunkan dari
  sumber yang benar-benar dipakai lewat helper `sourceLabel()`, dan pesan
  ditutup dengan penegasan *"tidak ada sumber cadangan yang dicoba"*.
- `resources/views/schedule/assy_scheduler/generate_modal.blade.php` dan
  `index.blade.php` — teks langkah "Clone data terbaru dari *database listing*"
  menjadi "Ambil data terbaru dari *API SIREP*".
- Komentar `// STEP 1: Clone listing data from mysql_listing` disesuaikan.

Penyebutan "database listing" yang **tetap dipertahankan** karena memang akurat:
`DbListingSource.php:31` (jalur DB sungguhan) dan `ListingSyncService.php:413`
(statistik, sudah dijaga `if ($this->source->name() === 'db')`).

---

## 5. Belum Terjawab (butuh keputusan tim PPC)

Perbaikan T-1 **tidak dapat diselesaikan tanpa tabel padanan nama**. Pemetaan
tidak bisa ditebak dari data — tidak ada kemiripan string yang aman antara,
misalnya, `B1-J42U` (master) dan kandidat SIREP `B1` / `AB1` / `AB1-LHD`.

Yang dibutuhkan: satu baris padanan untuk tiap conveyor master di bawah ini.

| `master_conveyor.conveyor` | `sirep_conveyor_code` yang benar |
|---|---|
| B1-CELL VOLTAGE | ? |
| B1-BAT CONT | ? |
| B2-DOOR RH | ? |
| B2-DOOR LH | ? |
| B1-J42U | ? |
| B1-J42U STATIC | ? |
| B2-J42U LHD | ? |
| B2-J42U RHD | ? |
| B1-P33A-J1 | ? |
| B1-STATIC-P33A | ? |
| B2-RH | ? |
| B2-LH | ? |
| B3-EGI | *(sudah cocok, boleh NULL)* |
| B3-ENG | *(sudah cocok, boleh NULL)* |

Pertanyaan tambahan: apakah satu conveyor master boleh memetakan ke **lebih dari
satu** conveyor SIREP (mis. `B2-RH` ← `AB1-RHD` + `AB6-67SJ0A-RHD`)? Bila ya,
`sirep_conveyor_code` bertipe `string(50)` tidak memadai dan perlu tabel pivot.

---

## 6. Cara Mereproduksi

```bash
cd c:/xampp/htdocs/ekanban2

# 1. Diagnostik koneksi + kecocokan nama conveyor
php artisan sirep:check
php artisan sirep:check --conveyor=B3-EGI --from=2026-07-13 --to=2026-07-31

# 2. Sinkron kapasitas (pratinjau dulu, lalu terapkan)
php artisan sirep:sync-conveyor
php artisan sirep:sync-conveyor --apply

# 3. Sinkron listing saja
php artisan tinker --execute="
  \$r = app(App\Services\ListingSyncService::class)->syncListingData('2026-08-18','2026-08-22');
  echo json_encode(\$r, JSON_PRETTY_PRINT);
"

# 4. Generate end-to-end (sync + generate)
php artisan tinker --execute="
  \$r = app(App\Services\AssySchedulerService::class)->generateSchedules('2026-08-18','2026-08-22');
  echo json_encode(\$r, JSON_PRETTY_PRINT);
"

# 5. Verifikasi asal-usul jadwal
mysql -u root ekanban2 -e "
  SELECT ls.source, COUNT(*) jadwal, SUM(a.is_lock) terkunci
  FROM assy_schedule a JOIN listing_stage ls ON ls.id = a.listing_id
  WHERE a.schedule >= '2026-08-18' AND a.schedule < '2026-08-23'
  GROUP BY ls.source;"
```

**Peringatan:** langkah 4 menghapus lalu membangun ulang jadwal **tidak terkunci**
pada rentang tersebut. Jadwal terkunci (`is_lock=1`) tetap dipertahankan. Ambil
dump `listing_stage` + `assy_schedule` sebelum menjalankannya di data yang
bernilai.

---

## 7. Dampak Uji Terhadap Data

Uji pada bagian 3.5 mengubah data nyata di database `ekanban2`:

- `assy_schedule` rentang 18–22 Agu 2026: **168 → 134 baris** (jadwal tidak
  terkunci dibangun ulang; 109 jadwal terkunci tidak tersentuh).
- `listing_stage` rentang sama: baris B3-ENG 22 Agu berubah `source` dari `db`
  menjadi `api` (rekonsiliasi bekerja).
- `master_conveyor`: B3-EGI `capacity` 135 → 136; `overtime_capacity` dan
  `capacity_synced_at` terisi untuk B3-EGI dan B3-ENG.

Database `ekanban` (sumber clone) **tidak tersentuh sama sekali**.

---

## 8. Catatan Deployment Terkait

- Root `index.php` pada repo meng-hardcode segmen URL `/jai_ekanban2/`, sedangkan
  folder deploy bernama `ekanban2`. Diubah agar menurunkan prefix dari
  `basename(__DIR__)` sehingga tidak terikat nama folder.
- Migrasi `2026_07_28_000001_add_sirep_fields_for_api_integration` berstatus
  *Pending* setelah clone DB dari `ekanban`, karena migrasi tersebut hanya ada di
  repo `jai_ekanban2`. Sudah dijalankan. Bersifat aditif dan punya `down()`.
- `APP_KEY` sengaja disamakan dengan instance `ekanban` karena datanya clone.
- `SESSION_COOKIE=ekanban2_session` diset agar sesi tidak bentrok dengan instance
  `ekanban` yang berjalan di host `localhost` yang sama.
- Tidak perlu `npm install` / `npm run build`: aset dilayani sebagai file statis
  di `public/`; `@vite` hanya dipakai di `welcome.blade.php` yang tidak terpakai.
