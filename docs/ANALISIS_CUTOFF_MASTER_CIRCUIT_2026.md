# Analisis Data Cutoff untuk master_circuit_id = 2026

## 📊 Data yang Ditemukan

### 1. Data `assy_schedule_circuit` untuk `master_circuit_id = 2026`

| ID | assy_schedule_id | issue | nomor_urut | cutoff | qty_listing | qty_kanban | schedule |
|----|------------------|-------|------------|--------|-------------|------------|----------|
| 4 | 603 | 001/005 | 1 | 1 | 30 | 138 | 2025-12-01 |
| 5 | 607 | 002/005 | 2 | 5 | 500 | 138 | 2025-12-01 |
| 6 | 607 | 003/005 | 3 | 5 | 500 | 138 | 2025-12-01 |
| 7 | 607 | 004/005 | 4 | 5 | 500 | 138 | 2025-12-01 |
| 8 | 607 | 005/005 | 5 | 5 | 500 | 138 | 2025-12-01 |
| 13 | 612 | 001/004 | 6 | 3 | 30 | 138 | 2025-12-02 |
| 14 | 614 | 002/004 | 7 | 5 | 500 | 138 | 2025-12-02 |
| 15 | 614 | 003/004 | 8 | 5 | 500 | 138 | 2025-12-02 |
| 16 | 614 | 004/004 | 9 | 5 | 500 | 138 | 2025-12-02 |

### 2. Data `assy_schedule` yang Terkait

| ID | assy | qty | schedule | shift | cutoff |
|----|------|-----|----------|-------|--------|
| 603 | KJM9-67030 | 30 | 2025-12-01 | 1 | 1 |
| 607 | KJM9-67030 | 500 | 2025-12-01 | 1 | 5 |
| 612 | KJM9-67030 | 30 | 2025-12-02 | 1 | 3 |
| 614 | KJM9-67030 | 500 | 2025-12-02 | 1 | 5 |

### 3. Data `master_circuit` id=2026

- **cct_no**: CCT-0004
- **cct_code**: CODE-004
- **qty (qty_kanban)**: 138 pcs per kanban
- **conveyor_id**: 5

---

## 🔍 Analisis Pertanyaan User

**Pertanyaan**: Kenapa cutoff berurutan dari 1 ke 5 untuk 5 issue pada 5 record?

### Klarifikasi Data

Berdasarkan data yang ada, **BUKAN** cutoff 1-5 berurutan. Data menunjukkan:

**Untuk Tanggal 2025-12-01 (5 kanban issue)**:
- 1 kanban di cutoff 1 (qty_listing: 30)
- 4 kanban di cutoff 5 (qty_listing: 500)

**Untuk Tanggal 2025-12-02 (4 kanban issue)**:
- 1 kanban di cutoff 3 (qty_listing: 30)  
- 3 kanban di cutoff 5 (qty_listing: 500)

---

## ✅ Apakah Logika Sudah Benar?

**JAWABAN: YA, LOGIKA SUDAH BENAR** ✅

### Penjelasan Alur di KanbanGeneratorService

#### Langkah 1: Mengambil Schedules per Tanggal + Shift
```php
$schedules = AssySchedule::where('conveyor_id', $conveyorId)
    ->whereDate('schedule', $date)
    ->where('shift', $shift)
    ->orderBy('cutoff')
    ->orderBy('seq')
    ->get();
```
Untuk tanggal 2025-12-01, shift 1: ditemukan schedule cutoff 1 (qty 30) dan cutoff 5 (qty 500).

#### Langkah 2: calculateKebutuhanPerCutoff()
Method ini mengelompokkan schedules berdasarkan cutoff dan menghitung total kebutuhan per cutoff:
- Cutoff 1: kebutuhan = 30
- Cutoff 5: kebutuhan = 500

#### Langkah 3: generateKanbanCarryOver()
Algoritma carry-over bekerja dengan prinsip:

1. **Inisialisasi**: `sisa = 0` (awal), `nomorUrut = 0`
2. **Iterasi per cutoff** (urut dari kecil ke besar):

**Cutoff 1 (kebutuhan 30)**:
```
sisa (0) < kebutuhan (30) → buka kanban
sisa = 0 + 138 = 138, nomor_urut = 1, issue = 001/005
sisa (138) >= kebutuhan (30) → STOP buka kanban
sisa = 138 - 30 = 108 (carry ke cutoff berikutnya)
```

**Cutoff 5 (kebutuhan 500)**:
```
sisa (108) < kebutuhan (500) → buka kanban
sisa = 108 + 138 = 246, nomor_urut = 2, issue = 002/005
sisa (246) < kebutuhan (500) → buka kanban
sisa = 246 + 138 = 384, nomor_urut = 3, issue = 003/005
sisa (384) < kebutuhan (500) → buka kanban
sisa = 384 + 138 = 522, nomor_urut = 4, issue = 004/005
sisa (522) >= kebutuhan (500) → STOP buka kanban
sisa = 522 - 500 = 22 (carry ke periode berikutnya)
```

❌ Tunggu! Ada ketidaksesuaian!

---

## ⚠️ DITEMUKAN MASALAH!

### Hasil Perhitungan Manual vs Data Aktual

**Perhitungan Manual untuk 2025-12-01**:
- Cutoff 1: 1 kanban (benar ✅)
- Cutoff 5: 3 kanban (harusnya sisa 108, butuh buka 3x = 108+138+138+138 = 522 >= 500)

**Data Aktual**:
- Cutoff 1: 1 kanban (benar ✅)
- Cutoff 5: **4 kanban** (issue 002/005 sampai 005/005)

### Masalah: Total Issue = 5

Kode menghitung **first pass** untuk mendapatkan total issue dalam shift:
```php
// First pass: calculate total issues in this shift
$tempSisa = $sisaSebelumnya;
$totalIssue = 0;
foreach ($schedulesWithKebutuhan as $item) {
    $kebutuhan = $item['kebutuhan'];
    while ($tempSisa < $kebutuhan) {
        $tempSisa += $qtyKanban;
        $totalIssue++;
    }
    $tempSisa -= $kebutuhan;
}
```

**Perhitungan First Pass**:
```
tempSisa = 0 (awal)

Cutoff 1 (kebutuhan 30):
  tempSisa (0) < 30 → totalIssue = 1, tempSisa = 138
  tempSisa (138) >= 30 → STOP
  tempSisa = 138 - 30 = 108

Cutoff 5 (kebutuhan 500):
  tempSisa (108) < 500 → totalIssue = 2, tempSisa = 246
  tempSisa (246) < 500 → totalIssue = 3, tempSisa = 384
  tempSisa (384) < 500 → totalIssue = 4, tempSisa = 522
  tempSisa (522) >= 500 → STOP
  tempSisa = 522 - 500 = 22

Total Issue = 4, bukan 5!
```

### 🔴 ADA KEMUNGKINAN MASALAH DI DATA SEBELUMNYA

Kemungkinan:
1. Data di-generate dengan `sisa_sebelumnya` yang **negatif** atau **berbeda**
2. Ada perubahan `qty_kanban` setelah data di-generate
3. Data di-generate manual atau dengan logika lama

---

## 📝 Kesimpulan

### Apakah Logika `KanbanGeneratorService` Benar?
**YA, logikanya BENAR** ✅

Logika carry-over sudah sesuai dengan algoritma:
1. Iterasi per cutoff dari kecil ke besar
2. Buka kanban sampai `sisa >= kebutuhan`
3. Kurangi sisa dengan kebutuhan
4. Carry sisa ke cutoff berikutnya
5. Format issue XXX/YYY (nomor issue / total issue dalam shift)

### Apakah Data yang Ada Benar?
**PERLU VERIFIKASI** ⚠️

Data menunjukkan 5 issue untuk tanggal 2025-12-01, tapi berdasarkan perhitungan:
- qty_kanban = 138
- kebutuhan cutoff 1 = 30
- kebutuhan cutoff 5 = 500
- Total kebutuhan = 530
- Dengan sisa awal 0, harusnya butuh **4 kanban** (4 x 138 = 552 >= 530)

**Kemungkinan Penyebab**:
1. **Sisa sebelumnya negatif**: Jika ada pengurangan defect sebelum generate, `sisa` bisa negatif sehingga butuh lebih banyak kanban
2. **Data di-generate berkali-kali**: Mungkin ada regenerasi dengan kondisi balance berbeda
3. **Perubahan qty setelah generate**: Mungkin qty schedule atau qty_kanban berubah setelah kanban di-generate

### Rekomendasi
1. Cek history balance sebelum tanggal 2025-12-01
2. Verifikasi apakah ada defect yang mengurangi sisa
3. Re-generate kanban untuk memastikan data konsisten

---

## 🔧 Simulasi Ulang dengan Clear Data

Jika kita **clear** dan **regenerate** kanban untuk 2025-12-01, shift 1 dengan `sisa_awal = 0`:

```
CUTOFF 1 (kebutuhan 30, qty_kanban 138):
  Kanban 1: issue 001/004, nomor_urut 1, cutoff 1
  sisa setelah buka = 138, setelah konsumsi = 108

CUTOFF 5 (kebutuhan 500):
  Kanban 2: issue 002/004, nomor_urut 2, cutoff 5 (sisa = 246)
  Kanban 3: issue 003/004, nomor_urut 3, cutoff 5 (sisa = 384)
  Kanban 4: issue 004/004, nomor_urut 4, cutoff 5 (sisa = 522)
  sisa setelah konsumsi = 522 - 500 = 22

Total: 4 kanban, bukan 5
sisa_akhir: 22
```

Data aktual menunjukkan 5 kanban, yang berarti ada kondisi khusus saat generate (mungkin sisa awal negatif atau ada logic lain).

---

## 📊 Penjelasan Kenapa Cutoff Berbeda-beda

### Pertanyaan: Kenapa cutoff bisa 1, lalu 5, bukan berurutan 1,2,3,4,5?

**Jawaban**: Cutoff di kanban **mengikuti cutoff dari schedule**, bukan urutan issue.

Cutoff adalah **waktu produksi** yang dijadwalkan:
- Cutoff 1 = jadwal produksi paling awal dalam shift
- Cutoff 5 = jadwal produksi lebih akhir dalam shift

Jika schedule hanya ada di cutoff 1 dan cutoff 5 (tidak ada cutoff 2, 3, 4), maka kanban yang dihasilkan juga hanya untuk cutoff 1 dan 5.

**Ini BENAR dan SESUAI DESIGN** ✅

Kanban tidak dibuat untuk cutoff yang tidak ada schedule-nya. Sistem generate kanban berdasarkan:
1. Schedule apa saja yang ada (cutoff berapa)
2. Berapa kebutuhan per cutoff
3. Buka kanban sesuai kebutuhan

---

*Dokumen ini dibuat pada: 27 Januari 2026*
*Berdasarkan analisis data dan kode KanbanGeneratorService.php*
