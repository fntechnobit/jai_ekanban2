# 📋 SCHEDULE VERIFICATION - FLOW & LOGIC

## 🔄 PROSES LENGKAP VERIFIKASI JADWAL

### 1️⃣ **TRIGGER VERIFICATION (User Action)**

**Lokasi:** Frontend → Modal Schedule Verification  
**File:** `resources/views/schedule/schedule_verification/index.blade.php`

**User melakukan:**
1. Buka modal verifikasi untuk **Conveyor + Date + Shift** tertentu
2. Atur ulang item (drag-drop antar cutoff, pindahkan dari source panel)
3. Klik tombol **"Verify"**

---

## 📊 STEP-BY-STEP VERIFICATION PROCESS

### **STEP 1: Frontend - Kirim Payload**

**File:** `public/js/schedule-verification.js` (lines 650-750)

**Data yang dikirim:**
```javascript
{
    conveyor_id: 6,
    date: "2025-12-01",
    shift: 1,
    cutoffs: [
        {
            cutoff: 1,
            items: [
                { id: 123, qty: 25, listing_id: 5472, ... },
                { source_id: 456, qty: 10, type: 'available', ... }  // Item dari source
            ]
        },
        { cutoff: 2, items: [...] },
        ...
    ],
    transferred: [  // Items yang dipindahkan dari tanggal/shift lain
        { source_id: 456, target_cutoff: 1, qty: 10 }
    ]
}
```

**Endpoint:** `POST /schedule/schedule-verification/verify`

---

### **STEP 2: Backend - verifySchedule() Method**

**File:** `app/Services/ScheduleVerificationService.php` (lines 400-600)

#### **2.1 Transaction Start**
```php
DB::beginTransaction();
```

#### **2.2 Save/Update Schedule Items**

**Process:**
```
JIKA ada data cutoffs:
  1. Ambil semua schedule existing untuk conveyor+date+shift
  2. DELETE semua schedule existing
  3. RECREATE schedule berdasarkan payload cutoffs:
     
     UNTUK setiap item:
       - JIKA item dari source (has source_id):
           → Ambil data dari source_id
           → CREATE schedule baru dengan data source
           → KURANGI qty source (atau delete jika habis)
       
       - JIKA item existing (id numeric):
           → Gunakan data original dari existing schedule
           → CREATE schedule baru dengan cutoff baru
       
       - JIKA item dari listing:
           → Ambil data dari listing_stage
           → CREATE schedule baru
```

**Code:**
```php
// Delete existing
AssySchedule::whereIn('id', $existingIds)->delete();

// Recreate dengan cutoff baru
foreach ($cutoffs as $cutoffData) {
    foreach ($cutoffData['items'] as $item) {
        if ($isFromSource) {
            // Copy from source + deduct qty
            $sourceItem = AssySchedule::find($item['source_id']);
            AssySchedule::create([...]);
            $this->deductSourceQuantity($sourceItem, $qty);
        } else {
            // Regular item
            AssySchedule::create([...]);
        }
    }
}
```

---

#### **2.3 Lock & Mark as Verified**

**Update ALL schedules untuk conveyor+date+shift:**
```php
AssySchedule::where('conveyor_id', $conveyorId)
    ->whereDate('schedule', $date)
    ->where('shift', $shift)
    ->update([
        'is_lock' => 1,              // 🔒 Lock schedule
        'verified_at' => now(),      // ✅ Timestamp verification
        'verified_by' => Auth::id(), // 👤 User ID yang verify
        'updated_by' => Auth::id(),
        'updated_at' => now()
    ]);
```

**Efek:** 
- Schedule **tidak bisa di-edit** lagi
- Schedule **tidak muncul** di panel source untuk tanggal/shift lain
- Status berubah jadi **"Verified"** di datatable

---

#### **2.4 Generate Kanban Data**

**Method:** `KanbanGeneratorService::generateKanbanForSchedule()`  
**File:** `app/Services/KanbanGeneratorService.php`

**Process:**
```
1. Ambil semua schedule untuk conveyor+date+shift
2. CLEAR existing kanban data untuk schedule ini
3. GENERATE CIRCUIT KANBANS:
   - Loop per assy code
   - Ambil circuit components dari master_circuit
   - Hitung kebutuhan per cutoff (qty assy × circuit usage)
   - Apply CARRY-OVER logic:
     * Sisa dari cutoff sebelumnya dibawa ke cutoff berikutnya
     * Generate nomor urut kanban berdasarkan balance terakhir
   - Save ke assy_schedule_circuit
   - Update kanban_balance_circuit

4. GENERATE SHIKAKE KANBANS:
   - Loop per assy code
   - Ambil shikake components dari master_shikake
   - Process per tipe (bonder, dbl-crimp, joint, twist, cutting, shield)
   - Apply CARRY-OVER logic per tipe
   - Save ke assy_schedule_shikake
   - Update kanban_balance_shikake
```

**Tables Affected:**
```
assy_schedule_circuit        → Detail circuit per kanban
assy_schedule_shikake        → Detail shikake per kanban
kanban_balance_circuit       → Sisa & nomor urut terakhir
kanban_balance_shikake       → Sisa & nomor urut terakhir per tipe
```

---

#### **2.5 Commit Transaction**
```php
DB::commit();
```

**Jika sukses:** Return success message  
**Jika error:** `DB::rollBack()` - semua perubahan dibatalkan

---

## 🔓 UNVERIFY PROCESS

**File:** `app/Services/ScheduleVerificationService.php` → `unverifySchedule()`

Unverify **tidak** sekadar membalik `is_lock`. Schedule dihapus lalu **dibangun ulang
dari `listing_stage`** supaya kembali ke alokasi asli sebelum diverifikasi.

**Urutan proses (dalam 1 transaction):**

| Step | Aksi |
|------|------|
| 0 | `restoreTransferredItemsToOrigin()` — kembalikan item transfer ke jadwal asal (jika asal masih unverified) |
| 1 | `reverseBalanceForScheduleGroup()` — balikkan kontribusi balance via generation ledger |
| 2 | `clearKanbanData()` — hapus kanban circuit & shikake milik grup ini |
| 3 | DELETE `assy_schedule` untuk conveyor+date+**shift ini saja** |
| 4 | Regenerate dari `listing_stage` (lihat di bawah) |

### ⚠️ Step 4 — tiga aturan yang wajib dipatuhi

#### 1. Listing di-query per `date` + `conveyor` SAJA — JANGAN difilter `shift`

Engine generate ([`AssySchedulerService`](app/Services/AssySchedulerService.php)) mengelompokkan
listing hanya per tanggal+conveyor, lalu menyebarnya ke tiap shift berdasarkan kapasitas.
Kolom `listing_stage.shift` **tidak pernah dipakai sebagai filter**.

> 🔴 **Khusus sumber API SIREP:** API tidak menyediakan `shift`, sehingga
> [`SirepListingAdapter`](app/Services/Listing/SirepListingAdapter.php) menulis **`shift = 0`**
> untuk semua baris. Filter `->where('shift', $shift)` karena itu **tidak akan pernah cocok**
> — setiap unverify menghasilkan 0 record dan baris tersangkut di status **"No Data"**.

Sebuah shift juga sering seluruhnya diisi **luberan (overflow)** dari baris listing yang ditandai
shift lain, jadi filter shift salah bahkan untuk data bersumber DB lama.

> 🐛 **Regresi yang pernah terjadi:** filter `->where('shift', $shift)` membuat unverify Shift 2
> menghasilkan "No Data" dan qty-nya hilang. Kebalikannya, unverify Shift 1 mengambil seluruh
> demand harian ke Shift 1 sementara Shift 2 tetap memegang miliknya → qty **terhitung dobel**.

#### 2. Jumlah shift dihitung dari demand PENUH, bukan sisa

`resolveShiftCount($conveyor, $fullDemand)` menentukan hari itu berjalan 1 atau 2 shift
(aturan PPC: qty ≥ 2 × kapasitas → 2 shift). Nilainya **harus** memakai `SUM(qty)` penuh —
kalau memakai sisa setelah pengurangan, hari 2-shift menyusut jadi 1 shift dan shift target
tidak akan pernah dibangun.

#### 3. Kurangi dulu demand yang dipegang shift lain

Karena hanya shift target yang dihapus, qty yang masih dipegang shift lain dikurangi lewat
`deductOtherShiftsFromListings()` (match `assy_schedule.listing_id` = `listing_stage.id`).
Sisanya barulah dialokasikan ke shift target, dengan seluruh shift lain diperlakukan **locked**.

**Invarian:** `SUM(assy_schedule.qty)` per tanggal+conveyor harus tetap sama dengan demand
`listing_stage` — tidak boleh ada yang hilang maupun dobel.

**Efek akhir:**
- Schedule **unlocked** (`is_lock = 0`), status jadi **"Pending"**
- Kanban circuit & shikake grup ini **DIHAPUS**, balance **DIKEMBALIKAN** via ledger
- Verify ulang akan men-generate kanban dari awal

---

## 📚 PENGARUH KE TABLE DATABASE

### **DIRECT IMPACT (Langsung affected)**

#### 1. `assy_schedule`
```sql
Columns affected:
- is_lock          → 0 → 1 (locked)
- verified_at      → NULL → TIMESTAMP
- verified_by      → NULL → user_id
- updated_at       → TIMESTAMP updated
```

**Behavior:**
- Schedule dengan `verified_at IS NOT NULL` **tidak muncul** di available source
- Schedule dengan `is_lock = 1` **tidak bisa di-edit**

---

#### 2. `assy_schedule_circuit`
```sql
Records CREATED per kanban:
- assy_schedule_id → FK to assy_schedule
- cct_no           → Circuit number/code
- cct_code         → Circuit sub-code
- qty_kebutuhan    → Total qty needed for this circuit
- qty_kanban       → Qty per kanban card
- qty_issue        → Qty to issue (XXX/YYY format)
- nomor_urut       → Sequential kanban number
- cutoff           → Cutoff number
- process_type     → Circuit process type
```

**Logic:**
- Clear existing untuk schedule ini
- Generate baru dengan carry-over logic
- Nomor urut berlanjut dari balance terakhir

---

#### 3. `assy_schedule_shikake`
```sql
Records CREATED per kanban:
- assy_schedule_id → FK to assy_schedule
- shikake_code     → Shikake component code
- shikake_name     → Component name
- qty_kebutuhan    → Total qty needed
- qty_kanban       → Qty per kanban card
- qty_issue        → Issue format XXX/YYY
- nomor_urut       → Sequential number per tipe
- cutoff           → Cutoff number
- process_type     → Shikake type (bonder/dbl-crimp/joint/twist/cutting/shield)
```

**Process Types:**
- BONDER (1)
- DBL_CRIMP (2)
- JOINT (3)
- TWIST (4)
- CUTTING (5)
- SHIELD (6)

---

#### 4. `kanban_balance_circuit`
```sql
Columns UPDATED:
- sisa             → Remainder qty carried over
- last_nomor_urut  → Last sequential number used
- updated_at       → TIMESTAMP
```

**Per:** `conveyor_id + master_circuit_id`

**Logic:**
```
Untuk setiap circuit di assy:
  1. Hitung total kebutuhan dari semua cutoff
  2. Tambahkan sisa dari balance sebelumnya
  3. Generate kanban (total + sisa) / qty_kanban
  4. Hitung sisa baru: (total + sisa) % qty_kanban
  5. Update balance.sisa
  6. Update balance.last_nomor_urut
```

---

#### 5. `kanban_balance_shikake`
```sql
Columns UPDATED:
- sisa             → Remainder per tipe
- last_nomor_urut  → Last number per tipe
- updated_at       → TIMESTAMP
```

**Per:** `conveyor_id + shikake_code + process_type`

**Why separate per type?**  
Karena satu shikake bisa dipakai untuk multiple process dengan qty berbeda.

---

### **INDIRECT IMPACT (Tidak langsung)**

#### 6. `master_circuit`
**Read-only reference**  
Digunakan untuk ambil:
- `cct_no`, `cct_code`
- `qty` (usage per assy)
- Link ke `master_assy`

#### 7. `master_shikake`
**Read-only reference**  
Digunakan untuk ambil:
- `shikake_code`, `shikake_name`
- `qty_bonder`, `qty_dblcrimp`, etc.
- Link ke `master_assy`

#### 8. `listing_stage`
**Read-only reference**  
Digunakan saat create schedule dari panel source:
- `assy`, `assycode`
- `seq`, `plt`, `mode`, `snp`, `snpa`

---

## 🔁 CARRY-OVER LOGIC

### **Konsep Dasar**

**Tujuan:** Minimize waste, maximize efficiency

**Prinsip:**
```
Jika qty kanban = 10:
  Cutoff 1 butuh 23 → Generate 2 kanban (20), sisa 3
  Cutoff 2 butuh 17 → (17 + 3 sisa) = 20 → Generate 2 kanban, sisa 0
  Cutoff 3 butuh 8  → (8 + 0 sisa) = 8 → Generate 0 kanban, sisa 8
  Cutoff 4 butuh 15 → (15 + 8 sisa) = 23 → Generate 2 kanban (20), sisa 3
  ... sisa 3 dibawa ke tanggal/shift berikutnya (via balance table)
```

**Formula:**
```php
$total = $kebutuhan + $sisa_sebelumnya;
$jumlah_kanban = floor($total / $qty_kanban);
$sisa_baru = $total % $qty_kanban;
```

**Nomor Urut:**
```php
$nomor_urut_start = $last_nomor_urut + 1;
$nomor_urut_end = $nomor_urut_start + $jumlah_kanban - 1;
```

---

## 📊 DIAGRAM FLOW

```
┌─────────────────────────────────────────────────────────────┐
│                    USER ACTION (Frontend)                    │
│  - Open modal (AT8, 2025-12-01, Shift 1)                   │
│  - Drag-drop items antar cutoff                             │
│  - Add items dari source panel (tanggal/shift lain)        │
│  - Click "Verify" button                                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│         AJAX POST /schedule-verification/verify             │
│  Payload: { conveyor_id, date, shift, cutoffs, transferred }│
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│         ScheduleVerificationController::verify()            │
│  → Call: scheduleVerificationService->verifySchedule()      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              DB::beginTransaction()                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 1: Save/Update Schedule Items                         │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 1. Get existing schedules                           │   │
│  │ 2. DELETE all existing                              │   │
│  │ 3. RECREATE with new cutoff arrangement:           │   │
│  │    - Regular items: Copy original data              │   │
│  │    - Source items: Copy + deduct source qty         │   │
│  │    - New items: Get from listing_stage             │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Table: assy_schedule                                       │
│  Action: DELETE → INSERT (recreate)                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 2: Lock & Mark Verified                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ UPDATE assy_schedule                                │   │
│  │ SET is_lock = 1,                                    │   │
│  │     verified_at = NOW(),                            │   │
│  │     verified_by = user_id                           │   │
│  │ WHERE conveyor_id = ? AND date = ? AND shift = ?   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Table: assy_schedule                                       │
│  Action: UPDATE (set lock & verified flags)                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 3: Generate Kanban Data                               │
│  → Call: kanbanGenerator->generateKanbanForSchedule()       │
└────────────────────┬────────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         ▼                       ▼
┌──────────────────┐    ┌──────────────────┐
│ Circuit Kanbans  │    │ Shikake Kanbans  │
└─────────┬────────┘    └─────────┬────────┘
          │                       │
          ▼                       ▼
┌─────────────────────────────────────────────────────────────┐
│  3.1 CLEAR Existing Kanban Data                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ DELETE FROM assy_schedule_circuit                   │   │
│  │ WHERE assy_schedule_id IN (schedule ids)            │   │
│  │                                                      │   │
│  │ DELETE FROM assy_schedule_shikake                   │   │
│  │ WHERE assy_schedule_id IN (schedule ids)            │   │
│  └─────────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  3.2 GENERATE Circuit Kanbans (with carry-over)            │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ FOR each unique circuit:                            │   │
│  │   1. Get balance (sisa, last_nomor_urut)            │   │
│  │   2. Calculate kebutuhan per cutoff                 │   │
│  │   3. Apply carry-over:                              │   │
│  │      - Add sisa to current cutoff                   │   │
│  │      - Generate kanbans                             │   │
│  │      - Calculate new sisa                           │   │
│  │   4. INSERT assy_schedule_circuit records           │   │
│  │   5. UPDATE kanban_balance_circuit                  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Tables:                                                    │
│  - assy_schedule_circuit  (INSERT)                          │
│  - kanban_balance_circuit (UPDATE)                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  3.3 GENERATE Shikake Kanbans (with carry-over per type)   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ FOR each unique shikake:                            │   │
│  │   FOR each process_type (bonder, dbl-crimp, ...):  │   │
│  │     1. Get balance per type                         │   │
│  │     2. Calculate kebutuhan per cutoff               │   │
│  │     3. Apply carry-over per type                    │   │
│  │     4. Generate kanbans with nomor_urut             │   │
│  │     5. INSERT assy_schedule_shikake records         │   │
│  │     6. UPDATE kanban_balance_shikake                │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Tables:                                                    │
│  - assy_schedule_shikake  (INSERT)                          │
│  - kanban_balance_shikake (UPDATE per type)                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              DB::commit()                                   │
│  ✅ All changes persisted                                   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              RESPONSE to Frontend                           │
│  { success: true, message: "...", affected: N }            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              UI UPDATE                                      │
│  - Reload datatable                                         │
│  - Show success message                                     │
│  - Status badge: Pending → Verified                         │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 KEY POINTS

### ✅ **ON VERIFY:**
1. Schedule items di-**recreate** dengan cutoff baru
2. Semua schedule untuk tanggal+shift di-**lock** (`is_lock=1`, `verified_at=NOW()`)
3. Kanban circuit & shikake di-**generate** dengan carry-over logic
4. Balance table di-**update** dengan sisa dan nomor urut terakhir

### ✅ **ON UNVERIFY:**
1. Item transfer di-**kembalikan** ke jadwal asal (jika asal masih unverified)
2. Balance di-**reverse** via generation ledger, kanban grup ini di-**HAPUS**
3. Schedule shift tersebut di-**DELETE**, lalu di-**regenerate** dari `listing_stage`
4. Query listing **TANPA filter shift** (API SIREP menulis `shift = 0`), jumlah shift dari
   demand penuh, dan demand milik shift lain dikurangi dulu — lihat
   [UNVERIFY PROCESS](#-unverify-process); salah satu saja meleset → "No Data" atau qty dobel

### ⚠️ **IMPORTANT:**
- Verified schedule **TIDAK MUNCUL** di source panel (filter: `whereNull('verified_at')`)
- Kanban nomor urut **BERLANJUT** antar schedule (tidak reset)
- Carry-over logic ensure **minimal waste** dalam pemotongan material
- Transaction ensure **all-or-nothing** (jika error, rollback semua)

---

## 📁 FILES INVOLVED

```
Backend:
├─ app/Http/Controllers/Schedule/ScheduleVerificationController.php
├─ app/Services/ScheduleVerificationService.php
├─ app/Services/KanbanGeneratorService.php
├─ app/Models/AssySchedule.php
├─ app/Models/AssyScheduleCircuit.php
├─ app/Models/AssyScheduleShikake.php
├─ app/Models/KanbanBalanceCircuit.php
└─ app/Models/KanbanBalanceShikake.php

Frontend:
├─ resources/views/schedule/schedule_verification/index.blade.php
├─ public/js/schedule-verification.js
└─ public/css/schedule-verification.css

Routes:
└─ routes/web.php (schedule-verification.verify, schedule-verification.unverify)
```

---

**Generated:** 2026-02-12  
**Author:** Schedule Verification System Documentation
