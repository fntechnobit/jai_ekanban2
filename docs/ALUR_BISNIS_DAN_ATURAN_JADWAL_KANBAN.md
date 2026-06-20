# Alur Bisnis & Aturan Jadwal Assy/Kanban — Dokumen Induk

> **Status:** ⭐ SUMBER UTAMA & SATU-SATUNYA (single source of truth). Semua dokumen lama
> (analisa sync, planning kanban, perbandingan, analisis cutoff, runbook) **sudah digabung ke sini**.
> **Berlaku untuk:** `jai_ekanban` (Laravel) **dan** `jai_filter_kanban` (CodeIgniter 3) — konsep sama,
> perbedaan implementasi ditandai per-app.
> **Terakhir diperbarui:** 2026-06-19.

Daftar isi: §1 Gambaran umum · §2 Komponen · §3 SIREP · §4 Sync · §5 Generate · **§6 Aturan CO1–CO5** ·
§7 Halaman verifikasi · §8 Verify · §9 Unverify · §10 Generate kanban (carry-over) · §11 Defect ·
§12 Struktur DB · §13 State machine · §14 Blueprint CI3 · §15 Risiko & kontrak · §16 Lokasi implementasi.

---

## 1. Gambaran umum (4 lapisan)

```
SIREP (PPIC)                e-Kanban / Filter-Kanban
─────────────               ─────────────────────────────────────────────
Print Listing Box ──sync──► listing_stage ──generate──► assy_schedule ──verify──► kanban (circuit + shikake)
(demand harian)             (staging SIREP)  (bagi shift     (jadwal per         (lock + carry-over +
                                              & cutoff)       shift/cutoff)        cetak ke lantai produksi)
```

Empat lapisan yang harus selalu dijaga batasnya:
1. **Sumber eksternal (SIREP)** — demand mentah.
2. **Staging lokal** (`listing_stage`) — snapshot stabil; pembatas antara SIREP dan engine internal.
3. **Jadwal assy** (`assy_schedule`) — tentative → final, per shift & cutoff.
4. **Turunan kanban + balance carry-over** — material yang dicetak ke lantai produksi.

### Peta tabel

| Peran | jai_ekanban | jai_filter_kanban |
|---|---|---|
| Sumber eksternal (SIREP) | koneksi `mysql_listing` (default DB `sirep`), tabel `listing`, model `App\Models\Listing` | DB `listing_ppc`, tabel `listing` |
| Master conveyor | `master_conveyor` | `m_conveyor` |
| Staging listing | `listing_stage` | `t_listing_stage` |
| Jadwal hasil generate | `assy_schedule` | `t_assy_schedule` |
| Kanban tercetak | `assy_schedule_circuit` / `assy_schedule_shikake` | `t_circuit_schedule` |
| Balance carry-over | `kanban_balance_circuit` / `kanban_balance_shikake` | (balance terkait) |
| Master assy & mapping | `master_assy` + `master_circuit_assy` / `master_shikake_assy` | `m_assy` + `t_circuit_assy` |

---

## 2. Komponen utama

**jai_ekanban (Laravel) — services:** `ListingSyncService`, `AssySchedulerService`, `ShiftCapacityCalculator`,
`ShiftLockChecker`, `ListingAllocator`, `ScheduleCleanupService`, `ScheduleVerificationService`, `KanbanGeneratorService`.

**jai_filter_kanban (CI3) — model/modul:** `Assy_schedule_model` (stage + generatePhp), `Verif_schedule_model`
(list, get_conveyor_info, verify_group, generate), `C_loading_model` (kanban-gen modul cutting).

UI utama (keduanya): halaman sync listing · generate/manage assy scheduler · schedule verification +
modal drag-drop antar cutoff/tanggal/shift.

---

## 3. Tahap 1 — Data di SIREP (Print Listing Box)

SIREP (sistem PPIC) menghasilkan **demand harian** per conveyor. Tiap baris berisi: Tanggal, Assy Code/Number,
**Level**, **SNP**, **Qty**, **No.Box**, Shift.

- **Qty** = unit yang harus diproduksi hari itu (mis. assy `295J1-6RA1A`, Qty 300).
- **SNP** (Standard Number per Pack) = isi per box → **jumlah box = Qty ÷ SNP**.
- **No.Box** = penomoran box berurutan (mis. `154–168`) → identitas kanban.
- Field source `listing` yang dipakai: `id_listing, cv, time, shift, assycode, assy, qty, seq, plt, mode, snp, snpa, carline`.

Ini "kebenaran demand". Aplikasi kanban **tidak mengubahnya**, hanya menyalin & menjadwalkan.

---

## 4. Tahap 2 — Sinkronisasi ke staging

**Tujuan:** snapshot lokal agar generate tidak bergantung query runtime ke SIREP.
- ekanban: `ListingSyncService` (dipanggil di `AssySchedulerService::generateSchedules` Step 1).
- filter_kanban: `Assy_schedule_model::stage()` (clone `listing_ppc.listing` → `t_listing_stage`).

**Urutan:** validasi koneksi → ambil `listing` per rentang `time` (urut `id_listing ASC`) → hapus aman
`listing_stage` rentang itu (kecuali yang terikat jadwal terkunci / ber-kanban) → insert record baru.

**Duplicate check (ekanban):** record dianggap sudah ada bila sama pada
`listing_date_time + conveyor + assycode + seq`.

**Pemetaan field** `listing → listing_stage`: `id_listing→id_listing`, `time→listing_date_time`, `cv→conveyor`,
`shift→shift`, `assycode/assy/carline/qty/seq/plt/mode/snp/snpa` (sama), + `synced_at` = waktu sekarang.

> ⚠️ **Kontrak:** staging adalah boundary stabil. **Jangan** generate langsung dari SIREP bila ingin perilaku identik.

---

## 5. Tahap 3 — Generate `assy_schedule` dari staging

**Service:** `AssySchedulerService::generateSchedules(start, end, conveyorId?)` (ekanban) /
`Assy_schedule_model::generatePhp(start, end)` (filter_kanban). Catatan: di filter_kanban, **unverify
(`do_cancel`) & `reset_and_restage_assy` memanggil ulang `generatePhp`** → cukup ubah satu method.

Urutan per grup **(tanggal × conveyor)**:

1. **Sync ulang staging dulu** (generate tidak berdiri sendiri).
2. **Ambil listing valid**: dalam rentang, `assycode`/`assy` tidak kosong, `qty>0`, dan **belum** punya
   jadwal terkunci/ber-kanban untuk kombinasi sama (listing terverifikasi tak boleh di-generate ulang).
3. **Group** by `DATE(listing_date_time) + conveyor`; cocokkan nama conveyor → master conveyor.
4. **Cek shift lock**: shift terkunci diberi kapasitas **0** (jadwal final tak ditimpa).
5. **Hapus hanya** `assy_schedule` yang **belum lock** (`is_lock=0`) untuk slot itu.
6. **Hitung kapasitas CO & alokasikan** listing (lihat §6 — aturan resmi). Alokasi **FIFO** by
   `id_listing`/`seq`; satu listing bisa pecah ke beberapa cutoff/shift (`rem_qty` tracking).
7. **Bulk insert** ke `assy_schedule` dengan `is_lock=0` / `is_verified=0`.

State awal hasil generate: `is_lock=0`, `verified_at=null`, `verified_by=null`.

> **Konsistensi:** Dashboard & halaman Assy-Scheduler (ekanban) memanggil method generate yang sama.

---

## 6. ⭐ ATURAN RESMI: kapasitas & alokasi cutoff CO1–CO5

### 6.1 Kapasitas tiap CO

```
per_cutoff   = floor(capacity / 4)          # CO1, CO2, CO3
CO4          = capacity - 3 × per_cutoff     # sisa → CO1..CO4 = tepat 1 × capacity
CO5 nominal  = round(0.875 × capacity / 4)   # cap 100 → round(21.875) = 22
```

> **PENTING:** CO5 nominal pakai **`round`**, bukan `floor` (cap 100 ⇒ **22**, bukan 21).
> Nilai nominal ini **sama** untuk CO5 semua shift dan hanya **angka tampilan di form** —
> CO5 shift terakhir (catch-all) **boleh melebihinya** ("Over").

### 6.2 Urutan isi & sifat cap

**1 shift:**
```
CO1 → CO2 → CO3 → CO4  (hard cap)  →  CO5 = SEMUA sisa (catch-all)
```
**2 shift:**
```
S1.CO1–4  →  S2.CO1–4  (hard cap)  →  S1.CO5 (di-cap di nominal)  →  S2.CO5 = SEMUA sisa (catch-all)
```
> Generalisasi: CO1–4 semua shift unlocked dulu; lalu shift unlocked **terakhir** = CO5 catch-all,
> shift unlocked **lebih awal** = CO5 di-cap nominal.

**Konsekuensi: 100% listing selalu terjadwal** (tidak ada yang dibuang).

### 6.3 Contoh resmi (cap 100/shift, nominal CO5 = 22) — `/X` = terisi/kapasitas

| Case | Shift | Listing | Hasil |
|---|---|---|---|
| **A** | 1 | 150 | CO1..4 = 25/25 ; **CO5 = 50/22** (catch-all) |
| **B** | 1 | 100 | CO1..4 = 25/25 ; CO5 = – |
| **C** | 2 | 200 | S1.CO1..4 & S2.CO1..4 = 25/25 ; CO5 = – |
| **D** | 2 | 220 | S2.CO1..4 penuh ; **S1.CO5 = 20/22** ; S2.CO5 = – |
| **E** | 2 | 250 | **S1.CO5 = 22/22** (penuh) ; **S2.CO5 = 28/22 (Over)** (catch-all) |
| **F** | 2 | 180 | S2.CO4 = 5/25 (parsial) ; CO5 = – |

### 6.4 Arti "Over Capacity"

- **Form (per CO):** "over" bila `terisi > nominal`. Hanya CO5 shift terakhir yang bisa over (Case E).
- **List/Schedule (per hari):** ditandai bila `demand_harian > shift_qty × (capacity + round(0.875 × capacity/4))`.
  - 1 shift cap 100 → batas 100+22 = **122** (Case A 150 → over).
  - 2 shift cap 100 → batas 2×(100+22) = **244** (Case E 250 → over; Case D 220 → tidak).
- "Over" hanya **indikator** — datanya tetap terjadwal & tetap jadi kanban. Untuk hilang: naikkan `capacity`.

### 6.5 ⚠️ Perbedaan dengan aturan LAMA (sudah tidak berlaku)

| | LAMA (usang) | SEKARANG (resmi) |
|---|---|---|
| CO5 nominal | `floor(0.875×cap/4)` = 21 | `round(0.875×cap/4)` = 22 |
| CO5 shift terakhir | di-cap (sisa di atas cap **dibuang**) | **catch-all** (semua sisa masuk) |
| Urutan 2-shift | S1.CO1–5 → S2.CO1–5 | S1.CO1–4 → S2.CO1–4 → S1.CO5 → S2.CO5 |
| Hasil | listing bisa < SIREP | listing selalu = SIREP |

---

## 7. Tahap 4 — Halaman verifikasi (Schedule List)

Halaman ini **lapisan review/finalisasi**, bukan generator. `getDatatableQuery()` membangun **grid penuh**
(semua tanggal × conveyor aktif × shift sesuai konfigurasi) lalu di-left join dengan agregat `assy_schedule` →
slot kosong tetap muncul sebagai **No Data** (bisa dibuka untuk tarik item dari tanggal lain).

Kolom: Conveyor · Dates · Shift · **Capacity** · **Listing** · **Assy** · **Status**.
- **Listing** `300 (1)` = qty 300, 1 jenis assy; badge `! over` bila lewat kapasitas nominal (§6.4).
- **Status:** `No Data` (belum ada jadwal) · `Pending` (`is_lock=0`) · `Verified` (`is_lock=1`).
  filter_kanban memakai `is_verified`: **0**=pending · **1**=verified · **2**=sudah jadi kanban.

**Source panel (H..H+10):** saat mode edit, panel kanan memuat item dari tanggal/shift lain. Aturan:
hanya `assy_schedule` `is_lock=0` & `verified_at IS NULL`; tanggal H..H+10; shift aktif saat ini tak tampil
sebagai source; urut `shift → cutoff → listing_id`. (Hanya jadwal tentative yang boleh dipinjam.)

---

## 8. Tahap 5 — Proses Verify

**Service:** `ScheduleVerificationService::verifySchedule(conveyorId, date, shift, cutoffs)` /
`Verif_schedule_model::verify_group(...)`. Verify adalah proses **komposit** (bukan sekadar flag).

1. **Transaction**.
2. Baca schedule existing slot itu (di-key by `id`) — untuk pertahankan atribut asli.
3. **Hapus** semua `assy_schedule` slot itu (strategi delete + recreate, bukan update per item).
4. **Recreate** sesuai payload cutoff final. 3 cabang item:
   - **A. Existing** pada shift target → salin data asli, pakai qty & cutoff terbaru.
   - **B. Transfer** dari source panel (`source_id`) → buat record baru, isi jejak
     `transferred_from_date/shift/cutoff/listing_id`, **kurangi qty source** (hapus bila habis).
   - **C. Fallback** ke `listing_stage` by `listing_id` bila tak ada di existing.
5. **Lock**: `is_lock=1` / `is_verified=1`, isi `verified_at`, `verified_by`.
6. **Generate kanban** (§10). **Gagal kanban → seluruh verify di-rollback** (jadwal tak boleh verified
   tanpa kanban konsisten). (filter_kanban: hanya conveyor yang benar-benar menghasilkan kanban yang
   dinaikkan ke `is_verified=2`.)

---

## 9. Tahap 5b — Proses Unverify

**Service:** `unverifySchedule(conveyorId, date, shift)` / `do_cancel(...)`.

1. Transaction.
2. **Kembalikan item transfer ke asal** bila asal masih unverified (bila asal sudah verified → item
   dianggap hilang, dicatat warning).
3. **Reverse balance** carry-over dari kanban lama.
4. **Hapus** kanban (`assy_schedule_circuit`/`_shikake` / `t_circuit_schedule`) untuk grup itu.
5. **Hapus** `assy_schedule` slot itu.
6. **Regenerate** dari `listing_stage` (engine generate yang sama → distribusi/CO5 konsisten).

> Kolom audit transfer (`transferred_from_*`) adalah **inti integritas data**, bukan fitur tambahan —
> tanpa itu unverify tak tahu ke mana item dikembalikan.

---

## 10. Tahap 6 — Generate kanban (carry-over)

**Tujuan:** ubah jadwal final → kebutuhan kanban material per cutoff + simpan **sisa carry-over** untuk
periode berikutnya.

**Mapping kebutuhan** (dari jadwal final, bukan listing langsung):
- ekanban: `assy_schedule.assy → master_assy → master_circuit / master_shikake` (via pivot
  `master_circuit_assy` / `master_shikake_assy`).
- filter_kanban: `t_assy_schedule → m_assy (GROUP BY var_assy, MIN id) → t_circuit_assy → m_circuit → m_conveyor`
  (`TRIM(mc.var_conveyor)=TRIM(s.var_conveyor)`, `is_verified=1`, `int_qty<>0`).

**Algoritma carry-over (2 pass)** — parameter: `lot` (qty per kanban dari master), `sisa` (carry dari periode
sebelumnya), `last_nomor_urut`:

```text
# Pass 1 — hitung total issue dalam shift
tempSisa = sisaSebelumnya
foreach cutoff (urut kecil→besar):
    while tempSisa < kebutuhan_cutoff: tempSisa += lot; totalIssue++
    tempSisa -= kebutuhan_cutoff

# Pass 2 — buat kanban
sisa = sisaSebelumnya; nomorUrut = last_nomor_urut; issue = 0
foreach cutoff:
    while sisa < kebutuhan_cutoff:
        sisa += lot; nomorUrut++; issue++
        buat kanban { cutoff, qty_listing=kebutuhan, qty_kanban=lot,
                      issue = sprintf('%03d/%03d', issue, totalIssue),
                      nomor_urut = sprintf('%04d', nomorUrut) }
    sisa -= kebutuhan_cutoff
# simpan: sisa_akhir → balance, nomorUrut → last_nomor_urut
```

- **Issue** `XXX/YYY` = nomor kanban dalam shift / total kanban dalam shift (reset tiap shift).
- **Nomor urut** = sequence **global** 4 digit, **lanjut antar shift** (dari `kanban_balance.last_nomor_urut`).
- **Barcode** = `{CONVEYOR}-{CCT/SHK_CODE}-{NOMOR_URUT}` (mis. `CV11-ABC123-0001`). **Release date** =
  tanggal verifikasi.
- **Kanban hanya dibuat untuk cutoff yang ada schedule-nya** (cutoff bisa 1 lalu 5 saja — itu benar).

**Contoh** (CV11, qty_kanban/lot 40, kebutuhan per CO 48/48/48/24, sisa awal 0): Pass 1 → 5 issue
(`001/005..005/005`), `sisa_akhir = 32`, dilanjut ke shift berikutnya.

**Balance reversal:** saat re-verify/unverify, sistem membalik `sisa` **TETAPI tidak membalik `nomor_urut`**
(cegah nomor duplikat). Penting agar hasil sama persis lintas implementasi.

**Beda per app:** ekanban menyimpan kanban **pre-generated** ke tabel + balance persisten + tracking print
(`is_printed`, `print_count`); filter_kanban berbasis JOIN + `t_circuit_schedule`. Kontrak bisnisnya sama.

---

## 11. Fitur Defect (pengurangan balance) — jai_ekanban

Mengurangi balance storage saat ada defect/reject (cutting/shikake). Jenis shikake: BONDER, DBL_CRIMP, JOINT,
SHIELD, TWIST.

- Tabel log: `defect_log_circuit`, `defect_log_shikake` (dipisah; lihat §12).
- Logika `recordDefect`: ambil balance → validasi (`qty_defect ≤ balance`, tak boleh negatif) → kurangi
  `kanban_balance_*.sisa` → catat ke `defect_log_*` (`balance_before/after`, `reason`, `created_by`) — semua
  dalam transaksi.
- Aturan: max defect ≤ balance · balance tak boleh negatif · setiap defect tercatat · tanggal ≤ hari ini.
- Halaman history punya toggle Circuit/Shikake; summary dipisah per tipe.

---

## 12. Struktur database inti

**`listing_stage`** kolom kunci: `id, id_listing, listing_date_time, conveyor, shift, assycode, assy, carline,
qty, seq, plt, mode, snp, snpa, synced_at`.

**`assy_schedule`** kolom kunci: `id, schedule, conveyor_id, listing_id, shift, cutoff, assycode, assy, qty,
seq, plt, mode, snp, snpa, is_lock, verified_at, verified_by, transferred_from_date/shift/cutoff/listing_id,
created_by, updated_by, timestamps`.

**Output kanban (ekanban) — tetap dipisah** (jangan digabung bila ingin perilaku sama):
- `assy_schedule_circuit` / `assy_schedule_shikake`: `assy_schedule_id, master_circuit_id`/`master_shikake_id`,
  `issue, nomor_urut, barcode_kanban, release_date, qty_listing, qty_kanban, cutoff, process` (shikake),
  `is_printed, print_count, last_printed_at`. **Tidak ada** unique `(assy_schedule_id, cct_no, cct_code)` —
  satu schedule+circuit boleh banyak kanban (issue 001/005…).
- `kanban_balance_circuit` / `kanban_balance_shikake`: `conveyor_id, master_circuit_id`/`master_shikake_id`,
  `sisa, last_nomor_urut, last_schedule_id, last_schedule_date, last_shift`, unique `(conveyor_id, master_*_id)`.
- `defect_log_circuit` / `defect_log_shikake`: `conveyor_id, master_*_id, (shikake_type), defect_date, shift,
  qty_defect, balance_before, balance_after, reason, created_by`.

> **Keputusan desain (final):** balance & defect **dipisah per tipe** (circuit/shikake) dengan **FK langsung
> ke `master_circuit_id`/`master_shikake_id`** (bukan string `cct_no+cct_code`) → integritas referensial,
> query tanpa filter `type`, identifier stabil.

---

## 13. State machine inti

```
listing_stage (snapshot lokal, boleh disync ulang selama tak melanggar jadwal verified)
  → generate → assy_schedule [Pending: is_lock=0, verified_at=null]
  → verify   → assy_schedule [Verified: is_lock=1, verified_at!=null] + kanban + balance
  → unverify → delete + regenerate → [Pending]
```
`assy_schedule_circuit/shikake`: hanya valid bila grup verified / baru generate; dibersihkan saat re-verify/unverify.

---

## 14. Blueprint port ke CodeIgniter 3 (pseudo-code)

Yang dipindahkan bukan struktur folder, tetapi **kontrak proses**. Pertahankan: staging tetap ada · generate
hormati shift lock · alokasi FIFO `id_listing`/`seq` · verify = recreate+lock+kanban · unverify =
reverse+restore+regenerate · audit transfer · `nomor_urut` tak dibalik · transaction boundary sama.

```php
function generate_schedules($start, $end, $conveyorId = null) {
    refresh_listing_stage($start, $end);
    $groups = group_by_date_and_conveyor(get_valid_listing_stage($start, $end, $conveyorId));
    begin_transaction();
    foreach ($groups as $g) {
        $cv = find_master_conveyor($g->conveyor_name); if (!$cv) continue;
        initialize_rem_qty($g->rows);
        $lock = get_shift_lock_status($g->date, $cv->id);
        delete_unlocked_schedule($g->date, $cv->id);
        $caps = calculate_shift_capacities($cv, $lock);
        premap_cutoff5($caps, $cv->capacity, sum_rem_qty($g->rows)); // §6: earlier capped, last catch-all
        // CO1-4 semua shift → CO5 forward (S1 capped, S2 catch-all)
        foreach (available_shifts($cv) as $shift) {
            if ($lock[$shift]) continue;
            bulk_insert(allocate_to_shift($g->rows, $caps[$shift], $shift, $cv->id, $g->date));
            if (sum_rem_qty($g->rows) == 0) break;
        }
    }
    commit();
}
// verify_schedule / unverify_schedule: ikuti §8 / §9 (recreate+lock+generate_kanban / reverse+restore+regenerate)
```

---

## 15. Risiko & kontrak konsistensi (wajib dijaga)

1. **Jangan hilangkan staging** — generate dari snapshot, bukan langsung SIREP.
2. **Jangan ubah urutan alokasi** — FIFO `id_listing`/`seq` menentukan hasil split.
3. **Verify bukan sekadar update flag** — recreate + lock + generate kanban + update balance, transactional.
4. **Jangan abaikan kolom audit transfer** — tanpa itu unverify rusak.
5. **Jangan rollback `nomor_urut` kanban** — hanya balik `sisa`.
6. **Pastikan transaction boundary sama** untuk verify & unverify.
7. **Saat mengubah aturan CO5:** ubah satu sumber (`ShiftCapacityCalculator::preMapCutoff5` /
   `Assy_schedule_model::generatePhp`), perbarui unit test, samakan nominal CO5 di form + flag over di list,
   jaga konsistensi ekanban ↔ filter_kanban.

---

## 16. Lokasi implementasi

### jai_ekanban (Laravel) — acuan + unit test
- `app/Services/Schedule/ShiftCapacityCalculator.php` — `calculateCutoff5Capacity()` (nominal `round`),
  `preMapCutoff5()` (budget c5: earlier capped, last catch-all), `calculateCutoffDistribution()`
- `app/Services/AssySchedulerService.php` → `generateSchedules()` (CO1–4 semua shift → CO5 forward `[1,2]`)
- `app/Services/Schedule/ListingAllocator.php` · `app/Services/Schedule/ShiftLockChecker.php` ·
  `app/Services/Schedule/ScheduleCleanupService.php`
- `app/Services/ScheduleVerificationService.php` — `getDatatableQuery()` (flag over), `getVerificationDetails()`
  (nominal CO5), `verifySchedule()`, `unverifySchedule()`
- `app/Services/KanbanGeneratorService.php` — carry-over kanban
- `public/js/schedule-verification.js`, `resources/views/schedule/schedule_verification/index.blade.php`
- **Unit test (case A–F):** `tests/Unit/Services/Schedule/ShiftCapacityCalculatorTest.php`
  → `vendor/bin/phpunit --filter ShiftCapacityCalculatorTest`

### jai_filter_kanban (CodeIgniter 3)
- `application/modules/scheduler/models/Assy_schedule_model.php` → `stage()`, `generatePhp()`
  (pre-map CO5 + urutan isi; **unverify lewat sini**)
- `application/modules/scheduler/models/Verif_schedule_model.php` → `get_conveyor_info()` (nominal CO5),
  `list()` (flag over), `verify_group()`, `generate()`
- `application/modules/scheduler/views/verif_schedule/index_action.php` — form cutoff
- `application/modules/cutting/models/C_loading_model.php` — kanban-gen alternatif (modul cutting)
