# Analisa Codebase: Sinkronisasi SIREP sampai Verifikasi Jadwal Assy/Listing

## Tujuan Dokumen

Dokumen ini merangkum alur bisnis dan alur teknis pada codebase saat ini, mulai dari pengambilan data listing dari sumber eksternal, staging ke database lokal, pembagian ke cutoff per shift, pembentukan jadwal assy/listing, sampai tampil dan diverifikasi pada halaman verifikasi jadwal assy/listing.

Dokumen ini sengaja ditulis agar bisa dipakai sebagai blueprint saat logika yang sama dipindahkan ke framework lain, khususnya CodeIgniter 3. Jadi fokus utama dokumen ini adalah:

- alur data
- aturan bisnis
- state perubahan tabel
- kontrak proses antar modul
- pseudo-code implementasi

## Catatan Penting Tentang Istilah SIREP

Di codebase ini istilah `SIREP` tidak muncul langsung pada service utama. Implementasi teknis yang ditemukan adalah:

- koneksi database eksternal bernama `mysql_listing`
- default nama database koneksi eksternal adalah `sirep`
- model sumber bernama `Listing`
- UI sinkronisasi menyebut sumber sebagai `Listing DB` atau `database listing (PPC)`

Artinya, untuk kebutuhan analisa ini, saya menganggap `SIREP` pada konteks bisnis user adalah sumber data eksternal yang diakses lewat koneksi `mysql_listing` dan tabel `listing`.

## Komponen Utama Yang Terlibat

### Sumber data eksternal

- Database connection: `mysql_listing`
- Tabel sumber: `listing`
- Model Laravel: `App\Models\Listing`

Field penting yang dipakai:

- `id_listing`
- `cv`
- `time`
- `shift`
- `assycode`
- `assy`
- `qty`
- `seq`
- `plt`
- `mode`
- `snp`
- `snpa`
- `carline`

### Staging lokal

- Tabel: `listing_stage`
- Fungsi: snapshot lokal hasil sinkronisasi dari source eksternal

### Jadwal assy/listing

- Tabel: `assy_schedule`
- Fungsi: hasil alokasi data `listing_stage` ke conveyor, shift, dan cutoff

### Hasil generate kanban

- `assy_schedule_circuit`
- `assy_schedule_shikake`
- `kanban_balance_circuit`
- `kanban_balance_shikake`

### Master pendukung

- `master_conveyor`
- `master_assy`
- `master_circuit`
- `master_shikake`

### Modul backend utama

- `ListingSyncService`
- `AssySchedulerService`
- `ShiftCapacityCalculator`
- `ShiftLockChecker`
- `ListingAllocator`
- `ScheduleVerificationService`
- `KanbanGeneratorService`

### Modul UI utama

- halaman sync listing
- halaman generate/manage assy scheduler
- halaman schedule verification
- modal verifikasi drag-drop antar cutoff dan antar tanggal/shift

## Ringkasan End-to-End Flow

Secara end-to-end, alurnya berjalan seperti ini:

1. Sistem mengambil data listing dari database eksternal `mysql_listing`.
2. Data tersebut disalin ke tabel staging lokal `listing_stage`.
3. Sistem generate `assy_schedule` dari `listing_stage` dengan aturan kapasitas conveyor, jumlah shift, dan pembagian cutoff.
4. Hasil generate muncul pada halaman verifikasi jadwal.
5. User dapat memindahkan item antar cutoff, bahkan menarik item dari tanggal/shift lain yang belum diverifikasi.
6. Saat user menekan `Verify`, sistem:
   - menyimpan ulang susunan cutoff final ke `assy_schedule`
   - me-lock jadwal tersebut
   - memberi jejak `verified_at` dan `verified_by`
   - meng-generate data kanban circuit dan shikake
   - meng-update balance carry-over
7. Saat user menekan `Unverify`, sistem:
   - membalik carry-over balance untuk jadwal tersebut
   - menghapus kanban yang sudah dihasilkan
   - mengembalikan item transfer ke jadwal asal jika masih memungkinkan
   - menghapus schedule pada grup itu
   - meregenerate ulang dari `listing_stage`

## Detail Tahap 1: Sinkronisasi Source Eksternal ke `listing_stage`

### Tujuan

Membuat snapshot lokal dari source eksternal agar proses generate schedule tidak bergantung langsung pada query runtime ke database eksternal.

### Implementasi sekarang

Service utama: `ListingSyncService`

Urutan proses:

1. Validasi koneksi `mysql_listing`.
2. Ambil data tabel `listing` berdasarkan rentang tanggal `time`.
3. Urutkan berdasarkan `id_listing ASC`.
4. Sebelum sync, sistem bisa menghapus data `listing_stage` untuk rentang tanggal yang sama, tetapi hanya untuk record yang tidak terikat pada `assy_schedule` yang sudah lock.
5. Untuk setiap record source:
   - cek apakah record sudah ada di `listing_stage`
   - jika belum ada, insert ke `listing_stage`
   - jika sudah ada, skip

### Aturan duplicate check

Satu record dianggap sudah ada jika kombinasi berikut sama:

- `listing_date_time`
- `conveyor`
- `assycode`
- `seq`

### Mapping field source ke staging

| Source `listing` | Staging `listing_stage` |
| --- | --- |
| `id_listing` | `id_listing` |
| `time` | `listing_date_time` |
| `cv` | `conveyor` |
| `shift` | `shift` |
| `assycode` | `assycode` |
| `assy` | `assy` |
| `carline` | `carline` |
| `qty` | `qty` |
| `seq` | `seq` |
| `plt` | `plt` |
| `mode` | `mode` |
| `snp` | `snp` |
| `snpa` | `snpa` |
| current time | `synced_at` |

### Kenapa `listing_stage` penting

Karena tabel ini adalah boundary stabil antara:

- sistem sumber eksternal
- proses generate jadwal internal

Saat dipindah ke CodeIgniter 3, boundary ini sebaiknya tetap dipertahankan. Jangan generate schedule langsung dari database SIREP jika ingin perilaku identik dengan sistem saat ini.

## Detail Tahap 2: Generate `assy_schedule` dari `listing_stage`

### Tujuan

Mengubah data listing mentah menjadi jadwal assy yang sudah terikat pada:

- tanggal schedule
- conveyor
- shift
- cutoff

### Service utama

`AssySchedulerService::generateSchedules(startDate, endDate, conveyorId)`

### Urutan proses detail

#### Step 2.1 - Sinkronisasi ulang staging lebih dulu

Sebelum generate, service ini selalu menjalankan:

- pembersihan `listing_stage` yang aman
- sinkronisasi ulang dari source eksternal

Jadi pada implementasi saat ini, `generate schedule` tidak berdiri sendiri. Ia bergantung pada refresh staging terlebih dahulu.

#### Step 2.2 - Ambil data `listing_stage` yang valid

Filter yang dipakai:

- dalam rentang tanggal
- `assycode` tidak null dan tidak kosong
- `assy` tidak null dan tidak kosong
- `qty > 0`
- belum punya `assy_schedule` yang sudah lock untuk kombinasi yang sama

Makna bisnisnya:

- listing yang sudah pernah masuk ke jadwal terverifikasi tidak boleh di-generate ulang

#### Step 2.3 - Group data per tanggal dan conveyor

Data di-group berdasarkan:

- `DATE(listing_date_time)`
- `conveyor`

Lalu nama conveyor string dari listing dicocokkan ke master `master_conveyor`.

#### Step 2.4 - Cek lock status per shift

Sebelum generate untuk satu tanggal-conveyor, sistem membaca apakah shift tertentu sudah lock.

Aturannya:

- shift yang sudah lock dianggap final
- shift yang lock diberi kapasitas `0`
- generate hanya berjalan untuk shift yang belum lock

Ini penting agar re-generate tidak menimpa jadwal yang sudah diverifikasi.

#### Step 2.5 - Hapus hanya schedule yang belum lock

Untuk tanggal dan conveyor yang sedang diproses, sistem membersihkan `assy_schedule` yang `is_lock = 0`.

Makna bisnisnya:

- data tentative boleh diganti
- data final tidak boleh disentuh

#### Step 2.6 - Hitung kapasitas per shift dan cutoff

Konfigurasi sumber:

- `master_conveyor.capacity`
- `master_conveyor.shift_qty`

Aturan pembagian:

- CO1 sampai CO4: kapasitas shift dibagi 4
- pembulatan sisa dimasukkan ke CO4
- CO5 tidak selalu ada
- CO5 hanya ditambahkan jika total kebutuhan melebihi kapasitas CO1-CO4
- kapasitas CO5 = `floor(0.875 * (capacity / 4))`

Contoh:

Jika kapasitas conveyor per shift = 100:

- CO1 = 25
- CO2 = 25
- CO3 = 25
- CO4 = 25
- CO5 = `floor(0.875 * 25) = 21`

#### Step 2.7 - Alokasi listing ke shift dan cutoff

Service yang dipakai: `ListingAllocator`

Aturan alokasi:

- semua listing diberi field tracking sementara `rem_qty`
- proses bersifat FIFO sesuai urutan data source
- urutan alokasi adalah:
  - Shift 1 CO1, CO2, CO3, CO4, CO5
  - lalu Shift 2 CO1, CO2, CO3, CO4, CO5
  - dan seterusnya sesuai `shift_qty`
- setiap cutoff mengambil qty dari listing pertama yang masih punya `rem_qty`
- jika qty listing lebih besar dari sisa kapasitas cutoff, listing di-split
- jika qty listing lebih kecil, cutoff lanjut mengambil dari listing berikutnya

Artinya satu record `listing_stage` bisa pecah menjadi beberapa record `assy_schedule` bila terbagi ke beberapa cutoff atau shift.

### Bentuk hasil `assy_schedule`

Kolom penting:

- `schedule`
- `conveyor_id`
- `listing_id`
- `shift`
- `cutoff`
- `assycode`
- `assy`
- `qty`
- `seq`
- `plt`
- `mode`
- `snp`
- `snpa`
- `is_lock`
- `verified_at`
- `verified_by`

State awal hasil generate:

- `is_lock = 0`
- `verified_at = null`
- `verified_by = null`

## Detail Tahap 3: Menampilkan Jadwal pada Halaman Verifikasi

### Tujuan halaman verifikasi

Halaman ini bukan generator utama. Halaman ini adalah lapisan review dan finalisasi.

Fungsinya:

- menampilkan semua kombinasi tanggal x conveyor x shift
- menandai status `Verified`, `Pending`, atau `No Data`
- membuka modal detail/verify
- mengambil source item dari tanggal/shift lain yang belum diverifikasi

### Cara datatable dibentuk

Service `getDatatableQuery()` tidak hanya membaca data existing, tetapi membangun grid lengkap:

- seluruh tanggal pada rentang filter
- seluruh conveyor aktif pada rentang itu
- seluruh shift sesuai konfigurasi conveyor

Lalu grid itu di-left join secara logis dengan agregat `assy_schedule`.

Akibatnya:

- kombinasi yang belum punya data tetap muncul sebagai `No Data`
- user tetap bisa membuka slot kosong dan menarik item dari source panel

### Status yang digunakan

- `No Data`: belum ada `assy_schedule` untuk kombinasi itu
- `Pending`: ada `assy_schedule`, tetapi belum lock
- `Verified`: ada `assy_schedule` dan `is_lock = 1`

## Detail Tahap 4: Modal Verifikasi dan Source Panel

### Data target

Saat modal dibuka, sistem mengambil semua `assy_schedule` untuk:

- `conveyor_id`
- `date`
- `shift`

Data lalu dikelompokkan per cutoff. Cutoff 5 selalu dipastikan ada, walaupun kosong.

### Kapasitas yang ditampilkan ke user

- CO1-CO4 memakai `capacity / 4`
- CO5 memakai `0.875 x (capacity / 4)`

UI menghitung:

- capacity per cutoff
- used qty per cutoff
- remain qty per cutoff
- warning jika over capacity

### Source panel H sampai H+10

Saat mode edit, panel kanan memuat source item dari tanggal lain atau shift lain.

Aturannya:

- hanya tampil data `assy_schedule` yang `is_lock = 0`
- hanya tampil data dengan `verified_at IS NULL`
- tanggal source diambil dari H sampai H+10
- untuk tanggal yang sama, shift aktif saat ini tidak ditampilkan sebagai source
- item diurutkan `shift -> cutoff -> listing_id`

Makna bisnisnya:

- hanya jadwal tentative yang boleh dipinjam atau dipindah
- jadwal verified tidak pernah boleh muncul sebagai source

### Payload verify dari frontend

Saat user klik `Verify`, frontend mengirim JSON berisi:

- `conveyor_id`
- `date`
- `shift`
- `cutoffs[]`
- `transferred[]`

Isi `cutoffs[]` merepresentasikan susunan final item di setiap cutoff setelah drag-drop dan perubahan qty.

Item dapat berasal dari 3 sumber:

- item existing pada target shift
- item hasil transfer dari source panel lain
- fallback berdasarkan `listing_id` ke `listing_stage`

## Detail Tahap 5: Proses Verify

### Tujuan

Membuat susunan jadwal final untuk satu kombinasi:

- conveyor
- tanggal
- shift

Sekaligus menutup kemungkinan perubahan lanjutan dan menghasilkan data kanban.

### Service utama

`ScheduleVerificationService::verifySchedule(conveyorId, date, shift, cutoffs)`

### Urutan proses detail

#### Step 5.1 - Mulai transaction

Semua proses verify dibungkus transaction database.

#### Step 5.2 - Ambil semua schedule existing pada target group

Data existing dibaca dulu dan di-key berdasarkan `id`.

Ini penting untuk mempertahankan atribut asli item ketika susunan cutoff diubah.

#### Step 5.3 - Hapus semua `assy_schedule` existing pada target group

Setelah data existing disimpan di memory, semua record target group dihapus.

Strategi yang dipakai adalah:

- bukan update per item
- tetapi delete lalu recreate sesuai payload final

Ini membuat hasil akhir identik dengan komposisi visual pada modal.

#### Step 5.4 - Recreate `assy_schedule` sesuai payload final

Ada tiga cabang logika:

##### A. Item existing pada target shift

Jika item berasal dari jadwal existing yang tadi dibaca:

- salin data asli dari existing schedule
- pakai qty hasil edit terbaru
- pakai cutoff baru

##### B. Item transfer dari source panel

Jika item memiliki `source_id`:

- ambil record source dari `assy_schedule`
- buat record baru di target shift/cutoff
- salin informasi asal ke kolom transfer:
  - `transferred_from_date`
  - `transferred_from_shift`
  - `transferred_from_cutoff`
  - `transferred_from_listing_id`
- kurangi qty pada record source
- jika qty source habis, record source dihapus

Ini adalah mekanisme penting untuk menjaga jejak asal item lintas tanggal/shift.

##### C. Fallback ke `listing_stage`

Jika item tidak ditemukan di existing schedule, sistem mencoba mencari `listing_id` pada `listing_stage`.

Jika ditemukan:

- create `assy_schedule` baru dari data staging

#### Step 5.5 - Lock jadwal

Setelah semua record final terbentuk, seluruh record pada target group di-update:

- `is_lock = 1`
- `verified_at = now()`
- `verified_by = current_user`

Makna bisnisnya:

- jadwal menjadi final
- jadwal tidak boleh dipakai lagi sebagai source panel
- jadwal tidak boleh terganti oleh regenerate biasa

#### Step 5.6 - Generate kanban

Setelah lock, sistem langsung memanggil generator kanban.

Jika generate kanban gagal:

- seluruh verify di-rollback
- jadwal tidak boleh berada di status verified tanpa kanban yang konsisten

Ini adalah keputusan desain yang benar dan sebaiknya dipertahankan pada versi CI3.

## Detail Tahap 6: Generate Kanban Circuit dan Shikake

### Tujuan

Mengubah jadwal assy final menjadi kebutuhan kanban material per cutoff, sekaligus menyimpan sisa carry-over untuk periode berikutnya.

### Service utama

`KanbanGeneratorService::generateKanbanForSchedule(conveyorId, date, shift)`

### Urutan proses

1. Ambil semua `assy_schedule` target group.
2. Reverse balance kontribusi kanban lama jika sebelumnya sudah pernah digenerate.
3. Hapus data lama pada `assy_schedule_circuit` dan `assy_schedule_shikake`.
4. Cari semua circuit yang terkait ke assy yang muncul di schedule.
5. Cari semua shikake yang terkait ke assy yang muncul di schedule.
6. Hitung kebutuhan per cutoff.
7. Jalankan algoritma carry-over.
8. Simpan hasil kanban per issue ke tabel output.
9. Update tabel balance.

### Cara mapping kebutuhan circuit/shikake

Generator tidak membaca langsung dari listing source. Generator membaca dari assy yang sudah final di `assy_schedule`, lalu memetakan ke master:

- assy schedule -> `master_assy`
- `master_assy` -> `master_circuit`
- `master_assy` -> `master_shikake`

Jadi boundary schedule final benar-benar menjadi sumber tunggal untuk generate kanban.

### Algoritma kebutuhan per cutoff

Untuk setiap circuit atau shikake:

1. group `assy_schedule` per cutoff
2. jumlahkan qty schedule yang assy-nya termasuk ke item master tersebut
3. hasilkan array kebutuhan per cutoff

### Algoritma carry-over

Konsep utamanya:

- `qty_kanban` adalah lot size
- `sisa` adalah sisa kebutuhan dari periode sebelumnya
- `last_nomor_urut` adalah nomor kanban terakhir

Rumus dasar:

```text
selama sisa < kebutuhan:
    sisa += qty_kanban
    nomor_urut += 1
    issue += 1

sisa -= kebutuhan
```

Generator melakukan dua pass:

1. pass pertama menghitung total issue pada shift
2. pass kedua membuat list kanban dengan format issue `XXX/YYY`

### Tabel hasil

#### `assy_schedule_circuit`

Menyimpan:

- `assy_schedule_id`
- `master_circuit_id`
- `cct_no`
- `cct_code`
- `issue`
- `nomor_urut`
- `barcode_kanban`
- `qrcode_shikake`
- `release_date`
- `qty_listing`
- `qty_kanban`
- `cutoff`

#### `assy_schedule_shikake`

Menyimpan:

- `assy_schedule_id`
- `master_shikake_id`
- `issue`
- `nomor_urut`
- `barcode_kanban`
- `release_date`
- `qty_listing`
- `qty_kanban`
- `cutoff`
- `process`

#### Balance table

`kanban_balance_circuit` dan `kanban_balance_shikake` menyimpan minimal:

- referensi conveyor + master item
- `sisa`
- `last_nomor_urut`
- jejak schedule terakhir

### Hal penting tentang balance reversal

Saat jadwal diverifikasi ulang atau di-unverify, sistem tidak hanya menghapus hasil kanban. Sistem juga membalik kontribusi `sisa` dari schedule group tersebut.

Namun ada satu keputusan penting:

- `nomor_urut` tidak dibalik

Tujuannya untuk mencegah duplikasi nomor kanban.

Ini adalah detail yang sangat penting bila ingin hasil pada sistem CI3 sama persis.

## Detail Tahap 7: Proses Unverify

### Tujuan

Mengembalikan satu schedule group ke kondisi sebelum diverifikasi, tanpa merusak konsistensi source transfer dan kanban balance.

### Service utama

`ScheduleVerificationService::unverifySchedule(conveyorId, date, shift)`

### Urutan proses detail

1. Mulai transaction.
2. Cek item transfer pada target group.
3. Kembalikan item transfer ke origin jika origin belum verified.
4. Jika origin sudah verified, item dianggap hilang dan dicatat sebagai warning.
5. Reverse balance carry-over dari kanban lama.
6. Hapus `assy_schedule_circuit` dan `assy_schedule_shikake` untuk group ini.
7. Hapus `assy_schedule` target group.
8. Regenerate ulang dari `listing_stage` untuk tanggal dan shift tersebut.
9. Commit.

### Kenapa transfer tracking penting

Karena tanpa kolom berikut:

- `transferred_from_date`
- `transferred_from_shift`
- `transferred_from_cutoff`
- `transferred_from_listing_id`

sistem tidak akan tahu ke mana item harus dikembalikan saat unverify.

Jadi kalau logika ini dipindahkan ke CI3, kolom audit transfer ini bukan fitur tambahan. Ini bagian inti dari integritas data.

## State Machine Data Inti

### `listing_stage`

State bisnis:

- raw snapshot lokal
- bukan final schedule
- boleh dibersihkan dan disinkron ulang selama tidak melanggar schedule verified

### `assy_schedule`

State bisnis:

- `Pending`: `is_lock = 0`, `verified_at = null`
- `Verified`: `is_lock = 1`, `verified_at != null`

Transisi:

- `listing_stage` -> generate -> `assy_schedule pending`
- `pending` -> verify -> `verified`
- `verified` -> unverify -> delete + regenerate -> `pending`

### `assy_schedule_circuit` / `assy_schedule_shikake`

State bisnis:

- hanya valid jika schedule group sudah verified atau baru selesai generate verify
- harus dibersihkan saat verify ulang atau unverify

## Blueprint Implementasi Di CodeIgniter 3

## Prinsip migrasi

Kalau targetnya adalah alur yang sama persis, maka yang dipindahkan bukan struktur folder Laravel-nya, tetapi kontrak prosesnya.

Kontrak proses yang wajib dipertahankan:

1. source eksternal -> `listing_stage`
2. `listing_stage` -> `assy_schedule pending`
3. `assy_schedule pending` -> review UI -> verify
4. verify -> lock schedule + generate kanban + update balance
5. unverify -> restore transfer + reverse balance + clear kanban + regenerate dari staging

### Rekomendasi struktur CI3

#### Model

- `Listing_model`
- `Listing_stage_model`
- `Assy_schedule_model`
- `Master_conveyor_model`
- `Master_circuit_model`
- `Master_shikake_model`
- `Kanban_balance_circuit_model`
- `Kanban_balance_shikake_model`

#### Library atau Service class

- `Listing_sync_service`
- `Assy_scheduler_service`
- `Shift_capacity_calculator`
- `Shift_lock_checker`
- `Listing_allocator`
- `Schedule_verification_service`
- `Kanban_generator_service`

#### Controller

- `System/Listing_sync.php`
- `Schedule/Assy_scheduler.php`
- `Schedule/Schedule_verification.php`

### Pseudo-code sinkronisasi staging di CI3

```php
function sync_listing_data($startDate, $endDate)
{
    test_mysql_listing_connection();

    $sourceRows = get_listing_source($startDate, $endDate); // order by id_listing asc

    begin_transaction();

    foreach ($sourceRows as $row) {
        if (exists_in_listing_stage($row->time, $row->cv, $row->assycode, $row->seq)) {
            continue;
        }

        insert_listing_stage(map_source_to_stage($row));
    }

    commit();
}
```

### Pseudo-code generate schedule di CI3

```php
function generate_schedules($startDate, $endDate, $conveyorId = null)
{
    refresh_listing_stage($startDate, $endDate);

    $listings = get_valid_listing_stage($startDate, $endDate, $conveyorId);
    $groups = group_by_date_and_conveyor($listings);

    begin_transaction();

    foreach ($groups as $group) {
        $conveyor = find_master_conveyor($group->conveyor_name);
        if (!$conveyor) {
            continue;
        }

        initialize_rem_qty($group->rows);
        $lockStatus = get_shift_lock_status($group->date, $conveyor->id);

        delete_unlocked_schedule($group->date, $conveyor->id);

        $shiftCaps = calculate_shift_capacities($conveyor, $lockStatus);
        premap_cutoff5($shiftCaps, $conveyor->capacity, sum_rem_qty($group->rows));

        foreach (available_shifts($conveyor) as $shift) {
            if ($lockStatus[$shift]) {
                continue;
            }

            $rows = allocate_to_shift($group->rows, $shiftCaps[$shift], $shift, $conveyor->id, $group->date);
            bulk_insert_assy_schedule($rows);

            if (sum_rem_qty($group->rows) == 0) {
                break;
            }
        }
    }

    commit();
}
```

### Pseudo-code verify di CI3

```php
function verify_schedule($conveyorId, $date, $shift, $cutoffs)
{
    begin_transaction();

    $existing = get_assy_schedule_group($conveyorId, $date, $shift);
    delete_assy_schedule_group($conveyorId, $date, $shift);

    foreach ($cutoffs as $cutoffData) {
        foreach ($cutoffData['items'] as $item) {
            if (is_transferred_item($item)) {
                $source = find_assy_schedule($item['source_id']);
                create_schedule_from_source($source, $conveyorId, $date, $shift, $cutoffData['cutoff'], $item['qty']);
                deduct_source_qty($source, $item['qty']);
            } elseif (isset($existing[$item['id']])) {
                recreate_schedule_from_existing($existing[$item['id']], $conveyorId, $date, $shift, $cutoffData['cutoff'], $item['qty']);
            } else {
                $listingStage = find_listing_stage($item['listing_id']);
                recreate_schedule_from_listing_stage($listingStage, $conveyorId, $date, $shift, $cutoffData['cutoff'], $item['qty']);
            }
        }
    }

    lock_schedule_group($conveyorId, $date, $shift, current_user_id());

    $result = generate_kanban_for_schedule($conveyorId, $date, $shift);
    if (!$result['success']) {
        rollback();
        return $result;
    }

    commit();
    return ['success' => true];
}
```

### Pseudo-code unverify di CI3

```php
function unverify_schedule($conveyorId, $date, $shift)
{
    begin_transaction();

    restore_transferred_items_to_origin($conveyorId, $date, $shift);
    reverse_balance_for_schedule_group($conveyorId, $date, $shift);
    clear_kanban_data($conveyorId, $date, $shift);
    delete_assy_schedule_group($conveyorId, $date, $shift);
    regenerate_from_listing_stage($conveyorId, $date, $shift);

    commit();
}
```

## Risiko Porting Yang Harus Dijaga

### 1. Jangan hilangkan staging table

Kalau CI3 langsung membaca source eksternal saat generate, perilaku bisa berbeda karena sistem sekarang mengandalkan snapshot `listing_stage`.

### 2. Jangan ubah urutan alokasi

Urutan FIFO berdasarkan `id_listing` dan `seq` mempengaruhi hasil split antar cutoff. Jika urutan berubah, jadwal final berubah.

### 3. Jangan treat verify sebagai sekadar update flag

Verify saat ini adalah proses komposit:

- recreate schedule final
- lock
- generate kanban
- update balance

### 4. Jangan abaikan transfer audit columns

Tanpa kolom transfer, unverify tidak akan bisa mengembalikan item lintas shift/tanggal dengan benar.

### 5. Jangan rollback nomor urut kanban

Sistem saat ini hanya membalik `sisa`, bukan `last_nomor_urut`, untuk mencegah nomor duplikat.

### 6. Pastikan transaction boundary sama

Process verify dan unverify wajib transactional. Kalau tidak, bisa terjadi status schedule lock tetapi kanban belum selesai, atau balance berubah sebagian.

## Rekomendasi Minimal Skema Tabel Jika Dibangun Ulang di CI3

### `listing_stage`

Minimal kolom:

- `id`
- `id_listing`
- `listing_date_time`
- `conveyor`
- `shift`
- `assycode`
- `assy`
- `carline`
- `qty`
- `seq`
- `plt`
- `mode`
- `snp`
- `snpa`
- `synced_at`

### `assy_schedule`

Minimal kolom:

- `id`
- `schedule`
- `conveyor_id`
- `listing_id`
- `shift`
- `cutoff`
- `assycode`
- `assy`
- `qty`
- `seq`
- `plt`
- `mode`
- `snp`
- `snpa`
- `is_lock`
- `verified_at`
- `verified_by`
- `transferred_from_date`
- `transferred_from_shift`
- `transferred_from_cutoff`
- `transferred_from_listing_id`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

### Output kanban

Tetap pisahkan:

- `assy_schedule_circuit`
- `assy_schedule_shikake`
- `kanban_balance_circuit`
- `kanban_balance_shikake`

Jangan gabungkan kalau targetnya adalah perilaku yang sama persis.

## Kesimpulan

Arsitektur proses pada codebase ini sebenarnya terdiri dari empat lapisan yang jelas:

1. `External listing/SIREP source`
2. `Staging lokal` melalui `listing_stage`
3. `Jadwal assy tentative/final` melalui `assy_schedule`
4. `Turunan kanban dan carry-over balance`

Kunci agar porting ke CodeIgniter 3 menghasilkan perilaku yang sama persis bukan ada pada framework, tetapi pada menjaga kontrak alur berikut:

- staging tetap ada
- generate tetap menghormati shift lock
- alokasi cutoff tetap FIFO dan sequential
- verify tetap bersifat recreate + lock + generate kanban
- unverify tetap bersifat reverse + restore + regenerate
- transfer antar tanggal/shift tetap punya audit trail

Kalau keenam kontrak itu dipertahankan, implementasi di CodeIgniter 3 akan menghasilkan behavior yang secara bisnis setara dengan sistem Laravel saat ini.

## Referensi Implementasi Saat Ini

File yang menjadi dasar analisa ini:

- `app/Services/ListingSyncService.php`
- `app/Services/AssySchedulerService.php`
- `app/Services/Schedule/ShiftCapacityCalculator.php`
- `app/Services/Schedule/ShiftLockChecker.php`
- `app/Services/Schedule/ListingAllocator.php`
- `app/Services/Schedule/ScheduleCleanupService.php`
- `app/Services/ScheduleVerificationService.php`
- `app/Services/KanbanGeneratorService.php`
- `app/Http/Controllers/Schedule/ScheduleVerificationController.php`
- `app/Http/Controllers/System/ListingSyncController.php`
- `public/js/schedule-verification.js`
- `resources/views/schedule/schedule_verification/index.blade.php`
- `resources/views/system/listing_sync/index.blade.php`
- `config/database.php`
- `database/migrations/2025_12_17_000001_create_listing_stage_table.php`
- `database/migrations/2025_12_18_163739_create_assy_schedule_table.php`
- `database/migrations/2025_12_28_000003_add_cutoff_to_assy_schedule_table.php`
- `database/migrations/2025_12_30_192656_add_verification_fields_to_assy_schedule_table.php`
- `database/migrations/2026_04_22_000001_add_transferred_from_to_assy_schedule_table.php`
- `database/migrations/2026_01_20_000003_add_kanban_fields_to_assy_schedule_circuit_table.php`
- `database/migrations/2026_01_20_000004_add_kanban_fields_to_assy_schedule_shikake_table.php`
