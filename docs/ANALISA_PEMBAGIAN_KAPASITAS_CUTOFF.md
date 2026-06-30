# Analisa Pembagian Kapasitas Assy Listing per Cut Off Shift

Dokumen ini menjelaskan **bagaimana kapasitas conveyor dibagi ke tiap cut off (CO1–CO5) pada setiap shift** saat proses *generate schedule* (Schedule Manage). Ini adalah tahap **alokasi kapasitas**, BUKAN tahap generate kanban / carry-over (lihat [PLANNING_KANBAN_GENERATION.md](PLANNING_KANBAN_GENERATION.md) untuk konsep yang berbeda itu).

> **Sumber kebenaran (single source of truth):**
> - [app/Services/Schedule/ShiftCapacityCalculator.php](../app/Services/Schedule/ShiftCapacityCalculator.php) — rumus kapasitas per cutoff
> - [app/Services/Schedule/ListingAllocator.php](../app/Services/Schedule/ListingAllocator.php) — pengisian listing ke tiap cutoff
> - [app/Services/AssySchedulerService.php](../app/Services/AssySchedulerService.php) — orkestrasi urutan alokasi
> - [tests/Unit/Services/Schedule/ShiftCapacityCalculatorTest.php](../tests/Unit/Services/Schedule/ShiftCapacityCalculatorTest.php) — spec tertulis (16 test, semua kasus A–F)
>
> Dokumen ini disusun agar **persis** mengikuti keempat file di atas. Jika kode berubah, perbarui dokumen ini.

---

## 1. Konsep Singkat

Setiap conveyor punya:

| Field (`master_conveyor`) | Arti | Default |
|---|---|---|
| `capacity` | Kapasitas **per shift** | 100 |
| `shift_qty` | Jumlah shift aktif (1 atau 2) | 2 |

Kapasitas satu shift dipecah menjadi **4 cut off pokok (CO1–CO4)** + **1 cut off luapan (CO5)**:

```
Kapasitas 1 shift (capacity)
        │
        ├── CO1 = floor(capacity / 4)
        ├── CO2 = floor(capacity / 4)
        ├── CO3 = floor(capacity / 4)
        ├── CO4 = sisa pembagian  ← menampung remainder
        └── CO5 = cut off luapan (hanya aktif bila listing > total CO1–4)
```

**Prinsip kunci:** 100% listing **selalu** terjadwal — tidak ada yang hilang — karena CO5 shift terakhir bersifat **catch-all** (menampung seluruh sisa).

---

## 2. Rumus Pembagian CO1–CO4

Implementasi: `ShiftCapacityCalculator::calculateCutoffDistribution()`.

```
c1 = floor(capacity / 4)
c2 = floor(capacity / 4)
c3 = floor(capacity / 4)
c4 = capacity - (c1 + c2 + c3)   // semua remainder masuk ke CO4
```

Contoh (sesuai unit test):

| capacity | CO1 | CO2 | CO3 | CO4 | Catatan |
|---:|---:|---:|---:|---:|---|
| 100 | 25 | 25 | 25 | 25 | habis dibagi rata |
| 101 | 25 | 25 | 25 | **26** | remainder 1 → CO4 |
| 103 | 25 | 25 | 25 | **28** | remainder 3 → CO4 |
| 10  | 2  | 2  | 2  | **4**  | remainder 4 → CO4 |
| 80  | 20 | 20 | 20 | 20 | habis dibagi rata |

> CO4 **selalu ≥** CO1/CO2/CO3 karena menampung sisa pembagian integer.

---

## 3. Rumus CO5 (Cut Off Luapan)

Implementasi: `ShiftCapacityCalculator::calculateCutoff5Capacity()` + `preMapCutoff5()`.

### 3.1 Nominal CO5

```
CO5 nominal = round(0.875 × capacity / 4)
```

Contoh: `capacity = 100` → `round(0.875 × 25)` = `round(21.875)` = **22**.

Nilai nominal ini dipakai sebagai **cap untuk shift yang BUKAN shift terakhir**, dan sebagai angka tampilan di form verifikasi.

### 3.2 Kapan CO5 aktif?

CO5 hanya aktif bila total listing **melebihi** gabungan kapasitas CO1–4 seluruh shift yang **tidak terkunci**:

```
totalCO14 = Σ kapasitas (CO1+CO2+CO3+CO4) semua shift tidak-terkunci
rem       = max(0, totalListing − totalCO14)

rem ≤ 0  → tidak ada CO5 (semua cukup di CO1–4)
rem > 0  → CO5 aktif, dibagi sesuai aturan 3.3
```

### 3.3 Aturan pengisian CO5 antar-shift

Iterasi shift tidak-terkunci secara berurutan:

- **Shift bukan terakhir** → `CO5 = min(rem, CO5 nominal)` (dibatasi nominal, mis. 22)
- **Shift terakhir** → `CO5 = seluruh rem` (**catch-all**, boleh melebihi nominal)

Karena shift terakhir adalah catch-all, **tidak ada listing yang terbuang**.

> **Shift terkunci** (`is_lock = 1`): seluruh CO1–CO5 = 0, dikeluarkan dari `totalCO14` maupun dari penempatan CO5. Bila shift terakhir terkunci, peran catch-all **otomatis jatuh ke shift tidak-terkunci terakhir** (mis. Shift 1).

---

## 4. Urutan Alokasi (AssySchedulerService)

Listing diambil dari `listing_stage`, dikelompokkan per **tanggal + conveyor**, lalu `totalQty = Σ rem_qty`. Urutan pengisian:

### 4.1 Conveyor 2-shift

```
Fase 1 (kapasitas dasar):
  Shift 1: CO1 → CO2 → CO3 → CO4
  Shift 2: CO1 → CO2 → CO3 → CO4
Fase 2 (luapan / CO5):
  Shift 1: CO5  (dibatasi nominal, mis. ≤22)
  Shift 2: CO5  (catch-all = semua sisa)
```

### 4.2 Conveyor 1-shift

```
CO1 → CO2 → CO3 → CO4 → CO5 (catch-all = semua sisa)
```

(Untuk 1-shift, satu-satunya shift adalah "shift terakhir" sehingga CO5-nya catch-all.)

---

## 5. Cara Mengisi Satu Cut Off (ListingAllocator)

Implementasi: `ListingAllocator::allocateToCutoff()`.

- Listing diproses **berurutan** (urutan query: `id_listing`, `listing_date_time`, `assycode`).
- Tiap cutoff diisi sampai **kapasitasnya habis**.
- Satu listing **boleh terbelah** ke beberapa cutoff: `take = min(rem_qty, sisa_kapasitas_cutoff)`.
- Setiap potongan menghasilkan **1 baris `assy_schedule`** bertanda `shift` + `cutoff`.

```
while (sisa kapasitas cutoff > 0):
    listing = listing pertama yang rem_qty > 0
    if tidak ada → berhenti
    take = min(listing.rem_qty, sisa kapasitas cutoff)
    buat baris assy_schedule (qty = take, cutoff = N, shift = S)
    listing.rem_qty   -= take
    sisa kapasitas    -= take
```

---

## 6. Tabel Skenario Bisnis (capacity = 100)

Diambil **langsung** dari unit test (kasus A–F + kasus shift terkunci). CO5 nominal = 22.

| Kasus | shift | Total Listing | S1.CO5 | S2.CO5 | Penjelasan |
|---|---:|---:|---:|---:|---|
| A | 1 | 150 | 50 | — | Sisa 50 → CO5 catch-all (lewat nominal) |
| B | 1 | 100 | 0 | — | Pas di CO1–4, tanpa CO5 |
| C | 2 | 200 | 0 | 0 | Pas di CO1–4 dua shift, tanpa CO5 |
| D | 2 | 220 | **20** | 0 | Sisa 20 ≤ 22 → cukup di S1.CO5 |
| E | 2 | 250 | **22** | **28** | S1.CO5 mentok 22, sisa 28 → S2.CO5 catch-all |
| F | 2 | 180 | 0 | 0 | Belum penuh CO1–4, tanpa CO5 |
| Locked | 2 (S2 terkunci) | 140 | **40** | 0 | S2 terkunci → catch-all jatuh ke S1.CO5 |

---

## 7. Contoh Perhitungan Lengkap

**Conveyor:** `capacity = 100`, `shift_qty = 2`, **total listing = 250** (Kasus E).

**Langkah 1 — Distribusi CO1–4 tiap shift:**

| Cutoff | Kapasitas |
|---|---:|
| CO1 | 25 |
| CO2 | 25 |
| CO3 | 25 |
| CO4 | 25 |
| **Total/shift** | **100** |

**Langkah 2 — Pre-map CO5:**

```
totalCO14 = 100 (S1) + 100 (S2) = 200
rem       = 250 − 200 = 50
CO5 nominal = 22
  Shift 1 (bukan terakhir) → min(50, 22) = 22   (rem sisa 28)
  Shift 2 (terakhir)       → 28  (catch-all)     (rem sisa 0)
```

**Langkah 3 — Urutan alokasi & kumulatif:**

| Urutan | Slot | Kapasitas | Kumulatif terjadwal |
|---|---|---:|---:|
| 1 | S1 CO1 | 25 | 25 |
| 2 | S1 CO2 | 25 | 50 |
| 3 | S1 CO3 | 25 | 75 |
| 4 | S1 CO4 | 25 | 100 |
| 5 | S2 CO1 | 25 | 125 |
| 6 | S2 CO2 | 25 | 150 |
| 7 | S2 CO3 | 25 | 175 |
| 8 | S2 CO4 | 25 | 200 |
| 9 | S1 CO5 | 22 | 222 |
| 10 | S2 CO5 | 28 | **250** ✅ |

Seluruh 250 pcs terjadwal — tidak ada sisa.

---

## 8. Ringkasan Aturan (cheat sheet)

1. **CO1=CO2=CO3 = floor(capacity/4)**; **CO4 = sisa** (menampung remainder).
2. **CO5 nominal = round(0.875 × capacity/4)**.
3. **CO5 aktif** hanya jika `totalListing > Σ CO1–4 shift tidak-terkunci`.
4. **Shift bukan terakhir:** CO5 ≤ nominal. **Shift terakhir:** CO5 = catch-all (semua sisa).
5. **Shift terkunci:** semua cutoff = 0; catch-all pindah ke shift tidak-terkunci terakhir.
6. **Urutan isi:** semua CO1–4 dulu (S1 lalu S2), baru CO5 (S1 nominal → S2 catch-all).
7. **100% listing selalu terjadwal** (jaminan dari catch-all).

---

## 9. Catatan Pemeliharaan

- Konstanta `0.875` dan pembagi `4` adalah inti aturan bisnis. Bila berubah, sesuaikan `ShiftCapacityCalculator` **dan** test-nya, lalu perbarui dokumen ini.
- Acuan perilaku yang benar adalah kode `preMapCutoff5()` + unit test. Komentar di `AssySchedulerService::generateSchedules()` Step 9 sudah diselaraskan dengan perilaku aktual (S1.CO5 dibatasi nominal lebih dulu, lalu S2.CO5 catch-all; pada 1-shift, CO5 = catch-all).
- Aturan CO5 ini selaras dengan catatan kanonik tim (CO5 nominal = round(0.875×cap/4), shift terakhir = catch-all).

---

*Dokumen dibuat: 29 Juni 2026 — berdasarkan kode aktual (working tree bersih, 16/16 unit test `ShiftCapacityCalculatorTest` lulus).*
