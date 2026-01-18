# Planning: Generate Kanban Data pada Verifikasi Jadwal

## 📋 Ringkasan Fitur

Ketika melakukan verifikasi jadwal (schedule verification), sistem akan secara otomatis melakukan generate data kanban untuk **Cutting/Circuit** dan **Shikake** yang nantinya siap untuk di-print. Data ini akan tersimpan di database dengan informasi:
- **Jumlah kanban** yang akan di-print
- **Issue number** (nomor issue kanban)
- **Nomor urut** (sequence dalam schedule)
- **Barcode kanban**
- **Release date**
- **Status print** (sudah/belum di-print)

---

## 🏗️ Arsitektur Saat Ini

### Tabel yang Ada

| Tabel | Deskripsi |
|-------|-----------|
| `assy_schedule` | Jadwal produksi per conveyor, shift, cutoff |
| `assy_schedule_circuit` | Tracking print status untuk circuit (grup by cct_no + cct_code) |
| `assy_schedule_shikake` | Tracking print status untuk shikake (link ke master_shikake_id) |
| `master_circuit` | Master data circuit dengan relasi ke assy via `master_circuit_assy` |
| `master_shikake` | Master data shikake dengan relasi ke assy via `master_shikake_assy` |
| `master_circuit_assy` | Pivot table: circuit ↔ assy |
| `master_shikake_assy` | Pivot table: shikake ↔ assy |

### Flow Saat Ini

```
[Listing Stage] → [Schedule Manage] → [Schedule Verification] → [Lock Schedule]
                                              ↓
                                      (is_lock = 1)
                                              ↓
                                    [E-Kanban Circuit/Shikake]
                                              ↓
                                      (Query ON-THE-FLY)
                                              ↓
                                        [Print Kanban]
```

**Masalah:**
- Data kanban (issue, sequence, barcode, release date) di-query langsung dari `master_circuit` dan `master_shikake`
- Tidak ada proses generate yang menghasilkan data spesifik per schedule
- Jumlah kanban dihitung on-the-fly, tidak tersimpan per schedule

---

## 🎯 Alur Program Baru

### Flow Baru yang Diinginkan

```
[Listing Stage] → [Schedule Manage] → [Schedule Verification]
                                              ↓
                                     ┌────────┴────────┐
                                     ↓                 ↓
                           [Generate Circuit    [Generate Shikake
                              Kanban Data]        Kanban Data]
                                     ↓                 ↓
                                     └────────┬────────┘
                                              ↓
                                       [Lock Schedule]
                                        (is_lock = 1)
                                              ↓
                                    [E-Kanban Circuit/Shikake]
                                              ↓
                                    (Query dari Generated Data)
                                              ↓
                                        [Print Kanban]
```

---

## 📊 Perubahan Struktur Database

### Tabel Baru: `kanban_balance` (Tracking Sisa & Nomor Urut)

Untuk menyimpan sisa dan nomor urut terakhir per CCT/Shikake per conveyor:

```sql
CREATE TABLE kanban_balance (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    conveyor_id BIGINT UNSIGNED NOT NULL,
    type ENUM('circuit', 'shikake') NOT NULL,
    
    -- Untuk Circuit
    cct_no VARCHAR(50) NULL,
    cct_code VARCHAR(50) NULL,
    
    -- Untuk Shikake
    master_shikake_id BIGINT UNSIGNED NULL,
    
    -- Balance tracking
    sisa INT NOT NULL DEFAULT 0,                -- Sisa kanban dari periode terakhir
    last_nomor_urut INT NOT NULL DEFAULT 0,     -- Nomor urut terakhir
    last_schedule_id BIGINT UNSIGNED NULL,      -- Schedule terakhir yang di-generate
    last_schedule_date DATE NULL,               -- Tanggal schedule terakhir
    last_shift INT NULL,                        -- Shift terakhir
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Unique constraint
    UNIQUE KEY unique_circuit_balance (conveyor_id, type, cct_no, cct_code),
    UNIQUE KEY unique_shikake_balance (conveyor_id, type, master_shikake_id),
    
    -- Foreign keys
    FOREIGN KEY (conveyor_id) REFERENCES master_conveyor(id) ON DELETE CASCADE,
    FOREIGN KEY (master_shikake_id) REFERENCES master_shikake(id) ON DELETE SET NULL
);
```

### Modifikasi Tabel Existing

#### 1. Modifikasi `assy_schedule_circuit`

```sql
ALTER TABLE assy_schedule_circuit ADD COLUMN:
- master_circuit_id BIGINT UNSIGNED NULL   -- Reference ke master_circuit
- issue VARCHAR(10) NOT NULL DEFAULT '001/001'  -- Format XXX/YYY
- nomor_urut VARCHAR(10) NOT NULL DEFAULT '0001' -- 4 digit sequence
- barcode_kanban VARCHAR(100) NULL         -- Generated barcode
- release_date DATE NULL                   -- Tanggal release/generate
- qty_listing INT NOT NULL DEFAULT 0       -- Kebutuhan qty dari schedule
- qty_kanban INT NOT NULL DEFAULT 0        -- Kapasitas per kanban
- cutoff INT NOT NULL DEFAULT 1            -- Cutoff number
```

#### 2. Modifikasi `assy_schedule_shikake`

```sql
ALTER TABLE assy_schedule_shikake ADD COLUMN:
- issue VARCHAR(10) NOT NULL DEFAULT '001/001'  -- Format XXX/YYY
- nomor_urut VARCHAR(10) NOT NULL DEFAULT '0001' -- 4 digit sequence
- barcode_kanban VARCHAR(100) NULL         -- Generated barcode
- release_date DATE NULL                   -- Tanggal release/generate
- qty_listing INT NOT NULL DEFAULT 0       -- Kebutuhan qty dari schedule
- qty_kanban INT NOT NULL DEFAULT 0        -- Kapasitas per kanban
- cutoff INT NOT NULL DEFAULT 1            -- Cutoff number
- process VARCHAR(20) NULL                 -- TWIST/BONDER/JOINT/SHIELD/DBL_CRIMP
```

---

## 🧮 Logika Perhitungan Kanban (Carry-Over System)

### Konsep Dasar - Referensi dari `jai_filter_kanban`

Sistem menggunakan **carry-over** yang sama dengan modul Print Withdrawal Shikake di `jai_filter_kanban` ([index_print.php](../../../jai_filter_kanban/application/modules/shikake/views/sk_assy/index_print.php)):

```php
/**
 * Logika dari jai_filter_kanban:
 * - Untuk tiap card (1..$wd_qty) dan tiap kode:
 *   - Selama carry < $int_pallet → buka coil baru (carry += lot, openedThisCard++).
 *   - Setelah cukup, card konsumsi $int_pallet dari carry (carry -= $int_pallet).
 *   - Kalau di card ini membuka coil baru (openedThisCard > 0) → kode itu tampil di card ini.
 *   - Kalau lot < pallet (contoh lot = 8, pallet = 16), loop while bisa jalan beberapa kali
 *     → muncul 2 baris di card yang sama.
 */
```

| Parameter | Deskripsi |
|-----------|-----------|
| **Qty Listing / int_pallet** | Kebutuhan per card/cutoff (dari `assy_schedule.qty` atau conveyor capacity) |
| **Qty Kanban / lot** | Kapasitas per coil/kanban (dari `master_circuit.qty` atau `master_shikake.qty`) |
| **Carry** | Sisa stok dari card/cutoff sebelumnya |
| **openedThisCard** | Jumlah kanban yang dibuka di card ini |
| **Issue** | Format XXX/YYY (card number / total cards dalam shift) |
| **wd_qty** | Total cards = `ceil(total_qty / int_pallet)` |

### Contoh Skenario (Sama dengan Request User)

**Conveyor: CV11 | CCT/Shikake: ABC123 | Qty Kanban (lot): 40 | Qty Listing (pallet): 48**

#### Shift 1 - Total Listing: 168 → wd_qty = ceil(168/48) = 4 cards (tapi CO4 hanya 24)

| Card/CO | Qty Listing | Carry Before | While Loop | Carry After | Kanban Opened | Issue |
|---------|-------------|--------------|------------|-------------|---------------|-------|
| 1 | 48 | 0 | 0<48 → +40, 40<48 → +40 = 80 | 80-48=32 | 2 | 001/005, 002/005 |
| 2 | 48 | 32 | 32<48 → +40 = 72 | 72-48=24 | 1 | 003/005 |
| 3 | 48 | 24 | 24<48 → +40 = 64 | 64-48=16 | 1 | 004/005 |
| 4 | 24 | 16 | 16<24 → +40 = 56 | 56-24=32 | 1 | 005/005 |

**Sisa untuk shift berikutnya: 32**

#### Shift 2 (Periode Selanjutnya) - dengan carry dari shift 1 = 32

| Card/CO | Qty Listing | Carry Before | While Loop | Carry After | Kanban Opened | Issue |
|---------|-------------|--------------|------------|-------------|---------------|-------|
| 1 | 48 | 32 | 32<48 → +40 = 72 | 72-48=24 | 1 | 001/004 |
| 2 | 48 | 24 | 24<48 → +40 = 64 | 64-48=16 | 1 | 002/004 |
| 3 | 48 | 16 | 16<48 → +40 = 56 | 56-48=8 | 1 | 003/004 |
| 4 | 24 | 8 | 8<24 → +40 = 48 | 48-24=24 | 1 | 004/004 |

**Sisa untuk periode berikutnya: 24**

### Algoritma Perhitungan (Adaptasi dari `jai_filter_kanban`)

```php
/**
 * Generate kanban dengan sistem carry-over
 * Referensi: jai_filter_kanban/application/modules/shikake/views/sk_assy/index_print.php
 * 
 * @param array $schedules - Schedules per cutoff, diurutkan berdasarkan cutoff
 * @param int $lot - Kapasitas per kanban (dari master data: master_circuit.qty / master_shikake.qty)
 * @param int $carryFromPrevious - Sisa dari periode sebelumnya (dari kanban_balance)
 * @param int $lastNomorUrut - Nomor urut terakhir dari periode sebelumnya
 */
function generateKanbanCarryOver($schedules, $lot, $carryFromPrevious, $lastNomorUrut)
{
    $carry = $carryFromPrevious;
    $nomorUrut = $lastNomorUrut;
    $kanbanList = [];
    
    // First pass: hitung total kanban yang akan dibuka di shift ini
    $tempCarry = $carryFromPrevious;
    $totalKanbanInShift = 0;
    foreach ($schedules as $schedule) {
        $pallet = $schedule->qty; // kebutuhan per cutoff
        while ($tempCarry < $pallet) {
            $tempCarry += $lot;
            $totalKanbanInShift++;
        }
        $tempCarry -= $pallet;
    }
    
    // Second pass: generate kanban dengan issue format XXX/YYY
    $issueInShift = 0;
    foreach ($schedules as $schedule) {
        $pallet = $schedule->qty; // kebutuhan per cutoff
        $openedThisCard = 0;
        
        // Buka kanban baru sampai carry >= pallet
        while ($carry < $pallet) {
            $carry += $lot;
            $nomorUrut++;
            $issueInShift++;
            $openedThisCard++;
            
            $kanbanList[] = [
                'assy_schedule_id' => $schedule->id,
                'cutoff' => $schedule->cutoff,
                'qty_listing' => $pallet,
                'qty_kanban' => $lot,
                'issue' => sprintf('%03d/%03d', $issueInShift, $totalKanbanInShift),
                'nomor_urut' => sprintf('%04d', $nomorUrut),
                'carry_before' => $carry - $lot, // carry sebelum buka kanban ini
            ];
        }
        
        // Konsumsi pallet dari carry
        $carry -= $pallet;
    }
    
    return [
        'kanban_list' => $kanbanList,
        'carry_after' => $carry, // sisa untuk periode berikutnya
        'last_nomor_urut' => $nomorUrut,
        'total_kanban' => $totalKanbanInShift,
    ];
}
```

### Perbedaan dengan `jai_filter_kanban`

| Aspek | `jai_filter_kanban` | `jai_ekanban` (Planning) |
|-------|---------------------|--------------------------|
| **Scope** | Per Assy (semua cutoff sekaligus) | Per Cutoff (tersimpan di DB) |
| **Carry Storage** | Variable dalam view (temporary) | Tabel `kanban_balance` (persistent) |
| **Issue Format** | cardNo/wd_qty per assy | issueInShift/totalKanbanInShift per CCT/Shikake |
| **Nomor Urut** | Tidak ada | Global sequence continue antar shift |
| **Output** | Langsung render HTML | Simpan ke DB untuk print nanti |

### Generate Issue Number

```php
// Issue format: XXX/YYY (sama dengan jai_filter_kanban)
// XXX = nomor kanban dalam shift (reset setiap shift baru)  
// YYY = total kanban dalam shift tersebut

$issue = sprintf('%03d/%03d', $issueInShift, $totalKanbanInShift);
// Contoh: 001/005, 002/005, 003/005, 004/005, 005/005
```

### Generate Nomor Urut (Sequence)

```php
// Nomor urut adalah sequence global yang TIDAK reset antar shift
// Continue dari periode sebelumnya (dari kanban_balance.last_nomor_urut)

$nomorUrut = $lastNomorUrutFromKanbanBalance + 1;
// Format: 0001, 0002, 0003, ... (4 digit)
```

### Generate Barcode Kanban

```php
// Format barcode: {CONVEYOR}-{CCT/SHK_CODE}-{NOMOR_URUT}
// Contoh: CV11-ABC123-0001

$barcodeKanban = sprintf(
    "%s-%s-%s",
    $conveyor,      // CV11
    $cctCode,       // ABC123
    $nomorUrut      // 0001
);
```

### Tentukan Release Date

```php
// Release date = tanggal verifikasi (saat generate kanban)
$releaseDate = now()->toDateString();
```

---

## 📝 Implementasi Service

### File Baru: `app/Services/KanbanGeneratorService.php`

```php
<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\AssyScheduleCircuit;
use App\Models\AssyScheduleShikake;
use App\Models\KanbanBalance;
use App\Models\MasterCircuit;
use App\Models\MasterShikake;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KanbanGeneratorService
{
    /**
     * Generate kanban data for a schedule group (conveyor + date + shift)
     */
    public function generateKanbanForSchedule(int $conveyorId, string $date, int $shift): array
    {
        $schedules = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->orderBy('cutoff')
            ->get();
            
        if ($schedules->isEmpty()) {
            return ['success' => false, 'message' => 'No schedules found'];
        }
        
        $circuitCount = $this->generateCircuitKanbans($conveyorId, $schedules);
        $shikakeCount = $this->generateShikakeKanbans($conveyorId, $schedules);
        
        return [
            'success' => true,
            'circuit_count' => $circuitCount,
            'shikake_count' => $shikakeCount,
            'message' => "Generated {$circuitCount} circuit and {$shikakeCount} shikake kanbans"
        ];
    }
    
    /**
     * Generate circuit kanbans with carry-over logic
     */
    private function generateCircuitKanbans(int $conveyorId, $schedules): int
    {
        $totalKanbans = 0;
        
        // Get unique circuits for this schedule group
        $circuitGroups = $this->getCircuitsForSchedules($schedules);
        
        foreach ($circuitGroups as $circuitKey => $circuitData) {
            // Get or create balance record
            $balance = KanbanBalance::firstOrCreate(
                [
                    'conveyor_id' => $conveyorId,
                    'type' => 'circuit',
                    'cct_no' => $circuitData['cct_no'],
                    'cct_code' => $circuitData['cct_code'],
                ],
                ['sisa' => 0, 'last_nomor_urut' => 0]
            );
            
            $result = $this->generateKanbanCarryOver(
                $schedules,
                $circuitData,
                $balance->sisa,
                $balance->last_nomor_urut
            );
            
            // Save generated kanbans
            foreach ($result['kanban_list'] as $kanban) {
                AssyScheduleCircuit::create([
                    'assy_schedule_id' => $kanban['assy_schedule_id'],
                    'cct_no' => $circuitData['cct_no'],
                    'cct_code' => $circuitData['cct_code'],
                    'master_circuit_id' => $circuitData['master_circuit_id'],
                    'issue' => $kanban['issue'],
                    'nomor_urut' => $kanban['nomor_urut'],
                    'barcode_kanban' => $this->generateBarcode(
                        $schedules->first()->conveyor->conveyor ?? 'CVX',
                        $circuitData['cct_code'],
                        $kanban['nomor_urut']
                    ),
                    'release_date' => now()->toDateString(),
                    'qty_listing' => $kanban['qty_listing'],
                    'qty_kanban' => $kanban['qty_kanban'],
                    'cutoff' => $kanban['cutoff'],
                    'is_printed' => false,
                    'print_count' => 0,
                ]);
                $totalKanbans++;
            }
            
            // Update balance
            $balance->update([
                'sisa' => $result['sisa_akhir'],
                'last_nomor_urut' => $result['nomor_urut_akhir'],
                'last_schedule_id' => $schedules->last()->id,
                'last_schedule_date' => $schedules->first()->schedule,
                'last_shift' => $schedules->first()->shift,
            ]);
        }
        
        return $totalKanbans;
    }
    
    /**
     * Core carry-over calculation
     */
    private function generateKanbanCarryOver($schedules, $masterData, $sisaSebelumnya, $lastNomorUrut): array
    {
        $qtyKanban = $masterData['qty_kanban'];
        $sisa = $sisaSebelumnya;
        $nomorUrut = $lastNomorUrut;
        $issueInShift = 0;
        $kanbanList = [];
        
        // First pass: calculate total issues in this shift
        $tempSisa = $sisaSebelumnya;
        $totalIssue = 0;
        foreach ($schedules as $schedule) {
            $kebutuhan = $this->getQtyListingForMaster($schedule, $masterData);
            while ($tempSisa < $kebutuhan) {
                $tempSisa += $qtyKanban;
                $totalIssue++;
            }
            $tempSisa -= $kebutuhan;
        }
        
        // Second pass: generate kanbans with issue format XXX/YYY
        foreach ($schedules as $schedule) {
            $kebutuhan = $this->getQtyListingForMaster($schedule, $masterData);
            
            while ($sisa < $kebutuhan) {
                $sisa += $qtyKanban;
                $nomorUrut++;
                $issueInShift++;
                
                $kanbanList[] = [
                    'assy_schedule_id' => $schedule->id,
                    'cutoff' => $schedule->cutoff,
                    'qty_listing' => $kebutuhan,
                    'qty_kanban' => $qtyKanban,
                    'issue' => sprintf('%03d/%03d', $issueInShift, $totalIssue),
                    'nomor_urut' => sprintf('%04d', $nomorUrut),
                ];
            }
            
            $sisa -= $kebutuhan;
        }
        
        return [
            'kanban_list' => $kanbanList,
            'sisa_akhir' => $sisa,
            'nomor_urut_akhir' => $nomorUrut,
            'total_issue' => $totalIssue,
        ];
    }
    
    /**
     * Generate barcode
     */
    private function generateBarcode(string $conveyor, string $code, string $nomorUrut): string
    {
        return sprintf('%s-%s-%s', $conveyor, $code, $nomorUrut);
    }
    
    /**
     * Clear kanban data for re-generate
     */
    public function clearKanbanData(int $conveyorId, string $date, int $shift): void
    {
        $scheduleIds = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->pluck('id');
            
        AssyScheduleCircuit::whereIn('assy_schedule_id', $scheduleIds)->delete();
        AssyScheduleShikake::whereIn('assy_schedule_id', $scheduleIds)->delete();
    }
}
```

### File Baru: `app/Models/KanbanBalance.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KanbanBalance extends Model
{
    protected $table = 'kanban_balance';
    
    protected $fillable = [
        'conveyor_id',
        'type',
        'cct_no',
        'cct_code',
        'master_shikake_id',
        'sisa',
        'last_nomor_urut',
        'last_schedule_id',
        'last_schedule_date',
        'last_shift',
    ];
    
    protected $casts = [
        'sisa' => 'integer',
        'last_nomor_urut' => 'integer',
        'last_shift' => 'integer',
        'last_schedule_date' => 'date',
    ];
}
```

---

## 🔄 Modifikasi `ScheduleVerificationService`

### Method `verifySchedule()` - Tambahan Logic

```php
public function verifySchedule($conveyorId, $date, $shift, $cutoffs = [])
{
    try {
        DB::beginTransaction();
        
        // ... existing cutoffs processing logic ...
        
        // === NEW: Generate Kanban Data ===
        $kanbanGenerator = app(KanbanGeneratorService::class);
        
        // Clear existing kanban data for this schedule group
        $kanbanGenerator->clearKanbanData($conveyorId, $date, $shift);
        
        // Generate new kanban data
        $kanbanResult = $kanbanGenerator->generateKanbanForSchedule($conveyorId, $date, $shift);
        
        if (!$kanbanResult['success']) {
            throw new \Exception($kanbanResult['message']);
        }
        
        // === END NEW ===
        
        // Lock all schedules
        $affected = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->update([
                'is_lock' => 1,
                'verified_at' => now(),
                'verified_by' => Auth::id(),
            ]);
        
        DB::commit();
        
        return [
            'success' => true,
            'message' => "Schedule verified. {$kanbanResult['circuit_count']} circuit and {$kanbanResult['shikake_count']} shikake kanban generated.",
            'kanban_data' => $kanbanResult
        ];
        
    } catch (\Exception $e) {
        DB::rollBack();
        return [
            'success' => false,
            'message' => 'Failed: ' . $e->getMessage()
        ];
    }
}
```

---

## 📋 Task Breakdown

### Phase 1: Database Migration

| No | Task | Priority | Est. Time |
|----|------|----------|-----------|
| 1.1 | Buat migration untuk tabel `kanban_balance` | High | 30 min |
| 1.2 | Buat migration untuk modifikasi `assy_schedule_circuit` | High | 30 min |
| 1.3 | Buat migration untuk modifikasi `assy_schedule_shikake` | High | 30 min |
| 1.4 | Buat Model `KanbanBalance` | High | 15 min |
| 1.5 | Update Model `AssyScheduleCircuit` dengan field baru | High | 15 min |
| 1.6 | Update Model `AssyScheduleShikake` dengan field baru | High | 15 min |
| 1.7 | Jalankan migration | High | 5 min |

### Phase 2: Kanban Generator Service

| No | Task | Priority | Est. Time |
|----|------|----------|-----------|
| 2.1 | Buat `KanbanGeneratorService.php` | High | 1 hr |
| 2.2 | Implement `generateCircuitKanbans()` dengan carry-over | High | 2 hr |
| 2.3 | Implement `generateShikakeKanbans()` dengan carry-over | High | 2 hr |
| 2.4 | Implement `generateKanbanCarryOver()` core logic | High | 1 hr |
| 2.5 | Implement barcode generation | High | 30 min |
| 2.6 | Implement `clearKanbanData()` untuk re-generate | High | 30 min |

### Phase 3: Integration

| No | Task | Priority | Est. Time |
|----|------|----------|-----------|
| 3.1 | Modifikasi `ScheduleVerificationService::verifySchedule()` | High | 1 hr |
| 3.2 | Modifikasi `ScheduleVerificationService::unverifySchedule()` | High | 30 min |
| 3.3 | Update `EkanbanCircuitService` untuk query dari generated data | High | 1 hr |
| 3.4 | Update `EkanbanShikakeService` untuk query dari generated data | High | 1 hr |
| 3.5 | Update print views untuk menggunakan generated barcode | Medium | 1 hr |

### Phase 4: Testing & Refinement

| No | Task | Priority | Est. Time |
|----|------|----------|-----------|
| 4.1 | Unit test untuk carry-over calculation | High | 1 hr |
| 4.2 | Integration testing dengan real data | High | 2 hr |
| 4.3 | Verify print kanban masih bekerja | High | 1 hr |
| 4.4 | Test multi-shift carry-over | High | 1 hr |
| 4.5 | Documentation update | Low | 30 min |

---

## ⚠️ Pertimbangan Penting

### 1. Re-verification Handling

Jika jadwal di-unverify kemudian di-verify ulang:
- **Clear kanban data** untuk schedule tersebut
- **Re-generate** dengan menggunakan balance yang tersimpan di `kanban_balance`
- Balance di `kanban_balance` **TIDAK di-reset** karena ini adalah tracking global

### 2. Balance Reset Scenario

Kapan balance perlu di-reset?
- Manual reset oleh admin (fitur terpisah)
- Pergantian periode produksi (tahunan/bulanan) - opsional

### 3. Data Konsistensi

```php
// Ketika unverify:
public function unverifySchedule($conveyorId, $date, $shift)
{
    // Option 1: Delete kanban data, revert balance (complex)
    // Option 2: Keep kanban data, just unlock schedule (recommended)
    
    // Recommended: Keep data, unlock only
    AssySchedule::where(...)->update(['is_lock' => 0, ...]);
}

// Ketika re-verify:
// - Clear existing kanban data untuk schedule ini
// - Re-generate dengan balance terkini dari kanban_balance
```

### 4. Unique Constraint

Setiap kanban harus unique. Constraint pada:
- `assy_schedule_circuit`: unique(assy_schedule_id, cct_no, cct_code, nomor_urut)
- `assy_schedule_shikake`: unique(assy_schedule_id, master_shikake_id, nomor_urut)

---

## � Perbandingan dengan Project `jai_filter_kanban`

### Overview Project `jai_filter_kanban`

Project `jai_filter_kanban` (CodeIgniter 3 + HMVC) memiliki konsep **Print Withdrawal** untuk Shikake yang berbeda dengan planning kita:

#### Arsitektur `jai_filter_kanban`

| Modul | Fungsi |
|-------|--------|
| `cutting/C_loading` | Generate circuit schedule via Stored Procedure |
| `cutting/C_filter_co` | Filter kanban per cutoff dengan scan barcode |
| `cutting/C_process` | Tracking proses cutting |
| `shikake/Sk_assy` | Print Withdrawal Shikake berdasarkan schedule |

#### Tabel Utama di `jai_filter_kanban`

| Tabel | Fungsi |
|-------|--------|
| `t_assy_schedule` | Jadwal assy dengan `is_verified` flag |
| `t_circuit_schedule` | Hasil generate circuit, int_status untuk tracking |
| `t_filter_cutoff` | Hasil filter kanban per cutoff (int_status: 1=process, 2=keep, 3=trash) |
| `m_shikake` | Master shikake dengan `var_cct_code`, `var_process`, `int_qty` |
| `t_shikake_assy` | Pivot table shikake ↔ assy |

---

### Perbandingan Flow

| Aspek | `jai_filter_kanban` | Planning `jai_ekanban` |
|-------|---------------------|------------------------|
| **Trigger Generate** | Manual via "Generate" button (Stored Procedure) | Otomatis saat Verify Schedule |
| **Data Source** | Query langsung dari master saat print | Pre-generate ke tabel saat verify |
| **Perhitungan Qty** | `ceil(total_qty / int_pallet)` - simple division | **Carry-over system** dengan sisa dari cutoff sebelumnya |
| **Issue Tracking** | Tidak ada - hanya card number | Issue format XXX/YYY dengan tracking |
| **Nomor Urut** | Tidak ada global sequence | Nomor urut global continue antar shift |
| **Barcode** | Dari master data | Di-generate saat verify |
| **Balance/Sisa** | Tidak ada | Tersimpan di `kanban_balance` table |
| **Print Status** | Tidak ada tracking | `is_printed`, `print_count`, `last_printed_at` |

---

### Detail Perbedaan Konsep

#### 1. Perhitungan Kanban - Referensi Kode Asli

**`jai_filter_kanban` (index_print.php lines 62-100):**
```php
// Distribusi per card dengan carry-over
for ($card = 1; $card <= $wd_qty; $card++) {
    $byProc = [];
    
    foreach ($orderedCodes as $code) {
        $st    = &$states[$code];
        $proc  = $st['proc'];
        $lot   = (int)$st['lot'];     // qty per coil/kanban
        $carry = &$st['carry'];       // sisa stok
        
        $openedThisCard = 0;
        
        // buka coil baru sampai stok sisa cukup untuk 1 card
        while ($carry < $int_pallet) {  // int_pallet = kebutuhan per card
            $carry += $lot;
            $openedThisCard++;
        }
        
        // konsumsi kebutuhan per card
        $carry -= $int_pallet;
        
        // hanya tampilkan kalau di card ini membuka coil baru
        if ($openedThisCard > 0) {
            for ($i = 0; $i < $openedThisCard; $i++) {
                $byProc[$proc][] = [
                    'var_cct_code' => $code,
                    'int_qty'      => $lot,
                ];
            }
        }
    }
    $cards_items[$card] = $byProc;
}
```

**Planning `jai_ekanban` (adaptasi):**
```php
// Logika yang sama, tapi:
// - Per CCT/Shikake code (bukan per assy)
// - Carry disimpan persistent di kanban_balance
// - Output disimpan ke assy_schedule_circuit / assy_schedule_shikake
// - Nomor urut global (continue antar shift)

foreach ($schedules as $schedule) {
    $pallet = $schedule->qty; // kebutuhan per cutoff
    $openedThisCard = 0;
    
    while ($carry < $pallet) {
        $carry += $lot;
        $nomorUrut++;      // Global sequence
        $issueInShift++;   // Reset per shift
        $openedThisCard++;
        
        // Simpan ke DB
        AssyScheduleCircuit::create([
            'assy_schedule_id' => $schedule->id,
            'issue' => sprintf('%03d/%03d', $issueInShift, $totalKanbanInShift),
            'nomor_urut' => sprintf('%04d', $nomorUrut),
            // ...
        ]);
    }
    $carry -= $pallet;
}

// Simpan carry ke kanban_balance untuk shift berikutnya
$balance->update(['sisa' => $carry, 'last_nomor_urut' => $nomorUrut]);
```

#### 2. Print Withdrawal Shikake

**`jai_filter_kanban`:**
- Print berdasarkan `var_assy` yang sudah verified
- Data diambil ON-THE-FLY dari `m_shikake` join `t_shikake_assy`
- Tidak ada tracking print, setiap print = fresh render
- Tidak ada barcode generate, gunakan barcode dari master

**Planning `jai_ekanban`:**
- Print berdasarkan data yang sudah di-generate saat verify
- Data sudah tersedia di `assy_schedule_shikake` dengan issue & nomor_urut
- Ada tracking: `is_printed`, `print_count`, `last_printed_at`
- Barcode di-generate unique per kanban

#### 3. Generate Circuit Schedule

**`jai_filter_kanban`:**
```php
// C_loading_model.php - menggunakan Stored Procedure
$this->call_sp(
    "CALL sp_generate_circuit_schedule(?, ?, ?, ?, @o_rows, @o_distinct)",
    [$int_area_id, $int_conveyor_id, $dt_shift, $int_shift]
);
```
- Generate via Stored Procedure
- Hasil masuk ke `t_circuit_schedule`
- Tidak ada perhitungan issue/nomor_urut

**Planning `jai_ekanban`:**
```php
// KanbanGeneratorService.php
$kanbanGenerator->generateKanbanForSchedule($conveyorId, $date, $shift);
```
- Generate via PHP Service dengan logic carry-over
- Hasil masuk ke `assy_schedule_circuit` dengan issue, nomor_urut, barcode
- Balance tersimpan di `kanban_balance`

#### 4. Filter Kanban (Scan Barcode)

**`jai_filter_kanban`:**
```php
// C_filter_co.php - Filter per cutoff dengan scan barcode
if ($cct_assy > 0) {
    $filter_stat = 1; // Sent to Cutting Process
} else {
    $filter_stat = 2; // Keep to Next Stock List
}
```
- Scan barcode untuk filter ke process/keep/trash
- Status: 1=process, 2=keep (next stock), 3=trash, 4=invalid

**Planning `jai_ekanban`:**
- Tidak ada fitur scan filter (fokus ke generate & print)
- Kanban langsung di-generate dengan data lengkap

---

### Keunggulan Planning `jai_ekanban`

| Fitur | `jai_filter_kanban` | `jai_ekanban` |
|-------|---------------------|---------------|
| **Carry-Over** | ✅ Ada (dalam view) | ✅ Ada (persistent di DB) |
| **Issue Tracking** | ✅ cardNo/wd_qty | ✅ issue/total per shift |
| **Nomor Urut Global** | ❌ Tidak ada | ✅ Continue antar shift |
| **Balance Storage** | ❌ Temporary (hilang setelah print) | ✅ Persistent di `kanban_balance` |
| **Print Tracking** | ❌ Tidak ada | ✅ is_printed, print_count |
| **Pre-Generated Data** | ❌ Generate saat print | ✅ Generate saat verify |
| **Barcode Unique** | ❌ Dari master | ✅ Generated per kanban |

---

### Rekomendasi Adopsi dari `jai_filter_kanban`

| Fitur | Adopsi? | Alasan |
|-------|---------|--------|
| Stored Procedure untuk generate | ❌ Tidak | PHP Service lebih maintainable |
| Filter scan barcode | ⚠️ Opsional | Bisa ditambahkan nanti jika diperlukan |
| Print structure (cards_by_proc) | ✅ Ya | Struktur print per process sudah bagus |
| Simple qty division fallback | ⚠️ Opsional | Sebagai fallback jika balance kosong |

---

Sebelum implementasi, perlu dikonfirmasi:

1. ✅ **Rumus Jumlah Kanban**: Sudah jelas - menggunakan sistem carry-over
   - Qty Kanban = nilai tetap dari master data (`master_circuit.qty` atau `master_shikake.qty`)
   - Generate kanban sampai sisa >= kebutuhan cutoff

2. ✅ **Format Issue**: XXX/YYY (contoh: 001/005)
   - XXX = nomor issue dalam shift (reset setiap shift)
   - YYY = total issue dalam shift tersebut

3. ✅ **Format Nomor Urut**: 4 digit (contoh: 0001)
   - Continue antar shift/periode (tidak reset)

4. ✅ **Format Barcode**: {CONVEYOR}-{CCT/SHK_CODE}-{NOMOR_URUT}
   - Contoh: CV11-ABC123-0001

5. ✅ **Release Date**: Tanggal verifikasi (saat generate kanban)

6. **Pertanyaan Tersisa**:
   - Apakah ada kebutuhan untuk **reset balance** secara berkala (misal: setiap bulan/tahun)?
   - Jika jadwal di-**unverify** dan ada kanban yang sudah di-print, apakah kanban tersebut:
     - Tetap valid? (recommended)
     - Harus di-void/cancel?
   - Apakah qty kanban (`master_circuit.qty` / `master_shikake.qty`) sudah tersedia di database?

---

## 📅 Timeline Estimasi

| Phase | Duration | Status |
|-------|----------|--------|
| Phase 1: Database | 2-3 jam | Pending |
| Phase 2: Service | 6-7 jam | Pending |
| Phase 3: Integration | 4-5 jam | Pending |
| Phase 4: Testing | 4-5 jam | Pending |
| Phase 5: Defect Feature | 8 jam | Pending |
| **Total** | **24-28 jam** | |

---

## ✅ Checklist Approval

- [x] Arsitektur database (tabel baru `kanban_balance` + modifikasi existing)
- [x] Rumus perhitungan kanban (carry-over system)
- [x] Format issue (XXX/YYY)
- [x] Format nomor urut (4 digit, continue antar shift)
- [x] Format barcode (CONVEYOR-CODE-NOMORURUT)
- [x] Logic release date (tanggal verifikasi)
- [x] Fitur Defect Cutting (pengurangan balance circuit)
- [x] Fitur Defect Shikake (pengurangan balance per jenis: bonder, dbl_crimp, joint, shield, twist)
- [x] Defect History (log pengurangan balance)
- [ ] Re-verification handling disetujui
- [ ] Balance reset policy disetujui
- [ ] Timeline disetujui

---

## 📊 Diagram Alur Generate Kanban

```
┌─────────────────────────────────────────────────────────────────┐
│                    VERIFY SCHEDULE                               │
│              (Conveyor: CV11, Date: 2026-01-18, Shift: 1)       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  1. Get all schedules for this conveyor/date/shift              │
│     Order by: cutoff ASC                                         │
│     Example: CO1(qty:48), CO2(qty:48), CO3(qty:48), CO4(qty:24) │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. Get unique circuits/shikakes for these schedules            │
│     via: master_assy → pivot table → master_circuit/shikake     │
│     Example: ABC123 (qty_kanban: 40)                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. For each circuit/shikake:                                   │
│     a. Get balance from kanban_balance table                    │
│        - sisa: 0 (atau dari periode sebelumnya)                 │
│        - last_nomor_urut: 0 (atau dari periode sebelumnya)      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. First Pass: Calculate total issues for this shift           │
│                                                                  │
│     CO1: sisa=0, kebutuhan=48                                   │
│         0 < 48 → +40 → issue++, sisa=40                         │
│         40 < 48 → +40 → issue++, sisa=80                        │
│         80 >= 48 → OK, sisa=80-48=32                            │
│     CO2: sisa=32, kebutuhan=48                                  │
│         32 < 48 → +40 → issue++, sisa=72                        │
│         72 >= 48 → OK, sisa=72-48=24                            │
│     CO3: sisa=24, kebutuhan=48                                  │
│         24 < 48 → +40 → issue++, sisa=64                        │
│         64 >= 48 → OK, sisa=64-48=16                            │
│     CO4: sisa=16, kebutuhan=24                                  │
│         16 < 24 → +40 → issue++, sisa=56                        │
│         56 >= 24 → OK, sisa=56-24=32                            │
│                                                                  │
│     Total Issues = 5                                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. Second Pass: Generate kanban records                        │
│                                                                  │
│     Issue 001/005, Nomor Urut 0001, Cutoff 1                    │
│     Issue 002/005, Nomor Urut 0002, Cutoff 1                    │
│     Issue 003/005, Nomor Urut 0003, Cutoff 2                    │
│     Issue 004/005, Nomor Urut 0004, Cutoff 3                    │
│     Issue 005/005, Nomor Urut 0005, Cutoff 4                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  6. Save to assy_schedule_circuit / assy_schedule_shikake       │
│                                                                  │
│     - assy_schedule_id: from schedule                           │
│     - cct_no/cct_code: from master                              │
│     - issue: "001/005"                                          │
│     - nomor_urut: "0001"                                        │
│     - barcode_kanban: "CV11-ABC123-0001"                        │
│     - release_date: 2026-01-18                                  │
│     - qty_listing: 48                                           │
│     - qty_kanban: 40                                            │
│     - cutoff: 1                                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  7. Update kanban_balance                                       │
│                                                                  │
│     - sisa: 32 (carry-over ke periode berikutnya)               │
│     - last_nomor_urut: 5                                        │
│     - last_schedule_date: 2026-01-18                            │
│     - last_shift: 1                                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  8. Lock Schedule (is_lock = 1)                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔴 Fitur Defect - Pengurangan Balance Storage

### Konsep

Fitur **Defect** digunakan untuk **mengurangi balance storage** ketika terjadi defect/reject pada cutting atau shikake. Balance yang tersimpan di `kanban_balance` akan dikurangi sesuai dengan jumlah defect yang diinput.

### Menu Defect

| Menu | Deskripsi |
|------|-----------|
| **Defect Cutting** | Pengurangan balance untuk circuit/cutting |
| **Defect Shikake** | Pengurangan balance untuk shikake dengan jenis-jenisnya |

### Jenis Defect Shikake

| Jenis | Tabel Master | Process Type |
|-------|--------------|--------------|
| Bonder | `master_shikake_bonder` | BONDER |
| Dbl Crimp | `master_shikake_dbl_crimp` | DBL_CRIMP |
| Joint | `master_shikake_joint` | JOINT |
| Shield | `master_shikake_shield` | SHIELD |
| Twist | `master_shikake_twist` | TWIST |

### Form Input Defect

#### Filter (Selection)
| Field | Deskripsi |
|-------|-----------|
| **Periode (Tanggal)** | Tanggal terjadinya defect |
| **Shift** | Shift saat defect terjadi (1, 2, 3) |
| **Conveyor** | Conveyor yang terkena defect |
| **CCT No / CCT Code** | (Untuk Cutting) Circuit yang defect |
| **Shikake** | (Untuk Shikake) Master shikake yang defect |
| **Jenis Shikake** | (Untuk Shikake) Bonder/Dbl Crimp/Joint/Shield/Twist |

#### Data Input
| Field | Deskripsi |
|-------|-----------|
| **Qty Defect** | Jumlah yang dikurangi dari balance |
| **Reason/Keterangan** | Alasan defect (optional) |

---

### Tabel Baru: `defect_log`

Untuk menyimpan history pengurangan balance:

```sql
CREATE TABLE defect_log (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    conveyor_id BIGINT UNSIGNED NOT NULL,
    type ENUM('circuit', 'shikake') NOT NULL,
    
    -- Untuk Circuit
    cct_no VARCHAR(50) NULL,
    cct_code VARCHAR(50) NULL,
    
    -- Untuk Shikake
    master_shikake_id BIGINT UNSIGNED NULL,
    shikake_type VARCHAR(20) NULL,          -- BONDER/DBL_CRIMP/JOINT/SHIELD/TWIST
    
    -- Defect Data
    defect_date DATE NOT NULL,              -- Tanggal defect
    shift INT NOT NULL,                     -- Shift saat defect
    qty_defect INT NOT NULL,                -- Jumlah yang dikurangi
    balance_before INT NOT NULL,            -- Balance sebelum dikurangi
    balance_after INT NOT NULL,             -- Balance setelah dikurangi
    reason TEXT NULL,                       -- Alasan defect
    
    -- Audit
    created_by BIGINT UNSIGNED NULL,        -- User yang input
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (conveyor_id) REFERENCES master_conveyor(id) ON DELETE CASCADE,
    FOREIGN KEY (master_shikake_id) REFERENCES master_shikake(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_defect_date (defect_date),
    INDEX idx_defect_conveyor (conveyor_id),
    INDEX idx_defect_type (type)
);
```

---

### Logika Pengurangan Balance

```php
/**
 * Reduce balance due to defect
 * 
 * @param string $type - 'circuit' atau 'shikake'
 * @param int $conveyorId - ID conveyor
 * @param array $params - Parameter sesuai type
 * @param int $qtyDefect - Jumlah yang dikurangi
 * @param array $meta - Metadata (date, shift, reason, etc)
 */
public function recordDefect(
    string $type, 
    int $conveyorId, 
    array $params, 
    int $qtyDefect, 
    array $meta
): array {
    DB::beginTransaction();
    
    try {
        // 1. Get current balance
        if ($type === 'circuit') {
            $balance = KanbanBalance::where([
                'conveyor_id' => $conveyorId,
                'type' => 'circuit',
                'cct_no' => $params['cct_no'],
                'cct_code' => $params['cct_code'],
            ])->first();
        } else {
            $balance = KanbanBalance::where([
                'conveyor_id' => $conveyorId,
                'type' => 'shikake',
                'master_shikake_id' => $params['master_shikake_id'],
            ])->first();
        }
        
        if (!$balance) {
            throw new \Exception('Balance record not found');
        }
        
        $balanceBefore = $balance->sisa;
        
        // 2. Validate: cannot reduce more than available
        if ($qtyDefect > $balanceBefore) {
            throw new \Exception("Cannot reduce {$qtyDefect} from balance {$balanceBefore}");
        }
        
        // 3. Reduce balance
        $balanceAfter = $balanceBefore - $qtyDefect;
        $balance->update(['sisa' => $balanceAfter]);
        
        // 4. Log the defect
        DefectLog::create([
            'conveyor_id' => $conveyorId,
            'type' => $type,
            'cct_no' => $params['cct_no'] ?? null,
            'cct_code' => $params['cct_code'] ?? null,
            'master_shikake_id' => $params['master_shikake_id'] ?? null,
            'shikake_type' => $params['shikake_type'] ?? null,
            'defect_date' => $meta['date'],
            'shift' => $meta['shift'],
            'qty_defect' => $qtyDefect,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reason' => $meta['reason'] ?? null,
            'created_by' => Auth::id(),
        ]);
        
        DB::commit();
        
        return [
            'success' => true,
            'message' => "Balance reduced from {$balanceBefore} to {$balanceAfter}",
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ];
        
    } catch (\Exception $e) {
        DB::rollBack();
        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}
```

---

### Implementasi

#### File Baru

| File | Deskripsi |
|------|-----------|
| `app/Http/Controllers/DefectController.php` | Controller untuk menu defect |
| `app/Services/DefectService.php` | Service untuk logika defect |
| `app/Models/DefectLog.php` | Model untuk defect_log table |
| `resources/views/defect/cutting.blade.php` | View menu defect cutting |
| `resources/views/defect/shikake.blade.php` | View menu defect shikake |
| `database/migrations/xxx_create_defect_log_table.php` | Migration |

#### Routes

```php
// routes/web.php

Route::prefix('defect')->name('defect.')->middleware('auth')->group(function () {
    // Defect Cutting
    Route::get('/cutting', [DefectController::class, 'cuttingIndex'])->name('cutting.index');
    Route::post('/cutting', [DefectController::class, 'cuttingStore'])->name('cutting.store');
    Route::get('/cutting/circuits', [DefectController::class, 'getCircuits'])->name('cutting.circuits');
    
    // Defect Shikake
    Route::get('/shikake', [DefectController::class, 'shikakeIndex'])->name('shikake.index');
    Route::post('/shikake', [DefectController::class, 'shikakeStore'])->name('shikake.store');
    Route::get('/shikake/list', [DefectController::class, 'getShikakes'])->name('shikake.list');
    
    // History
    Route::get('/history', [DefectController::class, 'history'])->name('history');
});
```

---

### UI Wireframe - Defect Cutting

```
┌──────────────────────────────────────────────────────────────────┐
│ 🔴 DEFECT CUTTING                                    [HISTORY]   │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Tanggal: [📅 2026-01-18]    Shift: [▼ 1    ]                   │
│                                                                  │
│  Conveyor: [▼ CV11 - CONVEYOR 11                    ]           │
│                                                                  │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  CCT No: [▼ ABC                                     ]           │
│  CCT Code: [▼ ABC123                                ]           │
│                                                                  │
│  Current Balance: ████████████████ 32 pcs                       │
│                                                                  │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  Qty Defect: [    10    ] pcs                                   │
│                                                                  │
│  Reason:                                                        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Wire damage during cutting process                         │ │
│  │                                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│              [❌ Cancel]  [✅ Submit Defect]                     │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

### UI Wireframe - Defect Shikake

```
┌──────────────────────────────────────────────────────────────────┐
│ 🔴 DEFECT SHIKAKE                                   [HISTORY]    │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Tanggal: [📅 2026-01-18]    Shift: [▼ 1    ]                   │
│                                                                  │
│  Conveyor: [▼ CV11 - CONVEYOR 11                    ]           │
│                                                                  │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  Jenis Shikake:                                                  │
│  ┌─────────┬───────────┬─────────┬──────────┬─────────┐         │
│  │ ○ Bonder│ ○ Dbl Crmp│ ● Joint │ ○ Shield │ ○ Twist │         │
│  └─────────┴───────────┴─────────┴──────────┴─────────┘         │
│                                                                  │
│  Shikake: [▼ JNT-001 - Joint Connection Type A      ]           │
│                                                                  │
│  Current Balance: ████████████████ 45 pcs                       │
│                                                                  │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  Qty Defect: [    5     ] pcs                                   │
│                                                                  │
│  Reason:                                                        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Joint connection failed during crimping                    │ │
│  │                                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│              [❌ Cancel]  [✅ Submit Defect]                     │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

### UI Wireframe - Defect History

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 📋 DEFECT HISTORY                                                            │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Filter: [📅 From: 2026-01-01] [📅 To: 2026-01-18]  [▼ Type: All]  [🔍 Filter]│
│                                                                              │
├────┬────────────┬───────┬────────────┬──────────────┬─────┬────────┬────────┤
│ No │ Date/Shift │ Conv  │ Type       │ Code         │ Qty │ Before │ After  │
├────┼────────────┼───────┼────────────┼──────────────┼─────┼────────┼────────┤
│ 1  │ 2026-01-18 │ CV11  │ Cutting    │ ABC123       │ 10  │   32   │   22   │
│    │ Shift 1    │       │            │              │     │        │        │
├────┼────────────┼───────┼────────────┼──────────────┼─────┼────────┼────────┤
│ 2  │ 2026-01-18 │ CV11  │ Shikake    │ JNT-001      │  5  │   45   │   40   │
│    │ Shift 1    │       │ (Joint)    │              │     │        │        │
├────┼────────────┼───────┼────────────┼──────────────┼─────┼────────┼────────┤
│ 3  │ 2026-01-17 │ CV12  │ Cutting    │ XYZ456       │  8  │   50   │   42   │
│    │ Shift 2    │       │            │              │     │        │        │
├────┼────────────┼───────┼────────────┼──────────────┼─────┼────────┼────────┤
│ 4  │ 2026-01-17 │ CV11  │ Shikake    │ BND-002      │ 12  │   60   │   48   │
│    │ Shift 1    │       │ (Bonder)   │              │     │        │        │
└────┴────────────┴───────┴────────────┴──────────────┴─────┴────────┴────────┘
│                                                                              │
│  Showing 1-4 of 4 entries                          [< Prev] [1] [Next >]    │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### Task Breakdown - Fitur Defect

#### Phase 5: Defect Feature

| No | Task | Priority | Est. Time |
|----|------|----------|-----------|
| 5.1 | Buat migration untuk tabel `defect_log` | High | 30 min |
| 5.2 | Buat Model `DefectLog.php` | High | 15 min |
| 5.3 | Buat `DefectService.php` dengan `recordDefect()` | High | 1 hr |
| 5.4 | Buat `DefectController.php` | High | 1 hr |
| 5.5 | Buat view `defect/cutting.blade.php` | High | 1.5 hr |
| 5.6 | Buat view `defect/shikake.blade.php` | High | 1.5 hr |
| 5.7 | Buat view `defect/history.blade.php` | Medium | 1 hr |
| 5.8 | Tambah routes untuk defect | High | 15 min |
| 5.9 | Tambah menu navigation | High | 15 min |
| 5.10 | Testing defect feature | High | 1 hr |

**Estimasi Total Phase 5: 8 jam**

---

### Validasi Business Rules

| Rule | Deskripsi |
|------|-----------|
| **Max Defect** | Qty defect tidak boleh melebihi balance yang tersedia |
| **Negative Balance** | Balance tidak boleh menjadi negatif |
| **Audit Trail** | Setiap defect harus tercatat di `defect_log` |
| **Date Validation** | Tanggal defect tidak boleh lebih dari hari ini |

---

### API Endpoints

```
GET  /defect/cutting                    - Halaman input defect cutting
POST /defect/cutting                    - Submit defect cutting
GET  /defect/cutting/circuits           - Get list circuits by conveyor (AJAX)

GET  /defect/shikake                    - Halaman input defect shikake  
POST /defect/shikake                    - Submit defect shikake
GET  /defect/shikake/list               - Get list shikakes by conveyor & type (AJAX)

GET  /defect/history                    - Halaman history defect
GET  /defect/history/export             - Export history ke Excel (optional)
```

---

*Document created: January 18, 2026*
*Last updated: January 18, 2026*
