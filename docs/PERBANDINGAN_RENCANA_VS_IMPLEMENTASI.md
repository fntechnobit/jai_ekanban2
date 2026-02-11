# Perbandingan: Rencana Awal vs Implementasi Aktual

**Dokumen ini membandingkan Planning awal (`PLANNING_KANBAN_GENERATION.md`) dengan implementasi yang sudah dilakukan.**

*Dibuat: 27 Januari 2026*

---

## 📊 Ringkasan Perubahan

| Aspek | Rencana Awal | Implementasi Aktual | Status |
|-------|--------------|---------------------|--------|
| Tabel `kanban_balance` | 1 tabel unified | 2 tabel terpisah | ✅ Diubah |
| Tabel `defect_log` | 1 tabel unified | 2 tabel terpisah | ✅ Diubah |
| Identifier Circuit | `cct_no` + `cct_code` | `master_circuit_id` | ✅ Diubah |
| Group ID E-Kanban | `assyId-cctNo-cctCode` | `assyId-masterCircuitId` | ✅ Diubah |
| Base Query E-Kanban | `assy_schedule` sebagai main | `assy_schedule_circuit/shikake` sebagai main | ✅ Diubah |
| Unique Constraint | `unique_circuit_group` | Dihapus | ✅ Diubah |

---

## 1. Perubahan Struktur Database

### 1.1 Tabel `kanban_balance` → Dipisah menjadi 2 Tabel

#### Rencana Awal (1 Tabel Unified)
```sql
CREATE TABLE kanban_balance (
    id BIGINT PRIMARY KEY,
    conveyor_id BIGINT,
    type ENUM('circuit', 'shikake'),  -- Discriminator
    
    -- Untuk Circuit (nullable)
    cct_no VARCHAR(50) NULL,
    cct_code VARCHAR(50) NULL,
    
    -- Untuk Shikake (nullable)
    master_shikake_id BIGINT NULL,
    
    -- Balance tracking
    sisa INT DEFAULT 0,
    last_nomor_urut INT DEFAULT 0,
    ...
);
```

#### Implementasi Aktual (2 Tabel Terpisah)

**Tabel `kanban_balance_circuit`:**
```sql
CREATE TABLE kanban_balance_circuit (
    id BIGINT PRIMARY KEY,
    conveyor_id BIGINT NOT NULL,
    master_circuit_id BIGINT NOT NULL,  -- ✅ Gunakan FK langsung
    
    sisa INT DEFAULT 0,
    last_nomor_urut INT DEFAULT 0,
    last_schedule_id BIGINT NULL,
    last_schedule_date DATE NULL,
    last_shift INT NULL,
    
    UNIQUE KEY (conveyor_id, master_circuit_id),
    FOREIGN KEY (master_circuit_id) REFERENCES master_circuit(id)
);
```

**Tabel `kanban_balance_shikake`:**
```sql
CREATE TABLE kanban_balance_shikake (
    id BIGINT PRIMARY KEY,
    conveyor_id BIGINT NOT NULL,
    master_shikake_id BIGINT NOT NULL,
    
    sisa INT DEFAULT 0,
    last_nomor_urut INT DEFAULT 0,
    ...
    
    UNIQUE KEY (conveyor_id, master_shikake_id),
    FOREIGN KEY (master_shikake_id) REFERENCES master_shikake(id)
);
```

#### Alasan Perubahan
1. **Normalisasi lebih baik** - Tidak ada field nullable yang hanya dipakai salah satu tipe
2. **Proper Foreign Key** - `master_circuit_id` langsung referensi ke `master_circuit` (bukan string `cct_no`+`cct_code`)
3. **Query lebih efisien** - Tidak perlu filter `WHERE type = 'circuit'`
4. **Constraint lebih jelas** - Unique constraint per tabel lebih straightforward

---

### 1.2 Tabel `defect_log` → Dipisah menjadi 2 Tabel

#### Rencana Awal (1 Tabel Unified)
```sql
CREATE TABLE defect_log (
    id BIGINT PRIMARY KEY,
    conveyor_id BIGINT,
    type ENUM('circuit', 'shikake'),
    
    -- Untuk Circuit
    cct_no VARCHAR(50) NULL,
    cct_code VARCHAR(50) NULL,
    
    -- Untuk Shikake
    master_shikake_id BIGINT NULL,
    shikake_type VARCHAR(20) NULL,
    ...
);
```

#### Implementasi Aktual (2 Tabel Terpisah)

**Tabel `defect_log_circuit`:**
```sql
CREATE TABLE defect_log_circuit (
    id BIGINT PRIMARY KEY,
    conveyor_id BIGINT NOT NULL,
    master_circuit_id BIGINT NOT NULL,  -- ✅ Gunakan FK langsung
    
    defect_date DATE NOT NULL,
    shift INT NOT NULL,
    qty_defect INT NOT NULL,
    balance_before INT NOT NULL,
    balance_after INT NOT NULL,
    reason TEXT NULL,
    created_by BIGINT NULL,
    
    FOREIGN KEY (master_circuit_id) REFERENCES master_circuit(id)
);
```

**Tabel `defect_log_shikake`:**
```sql
CREATE TABLE defect_log_shikake (
    id BIGINT PRIMARY KEY,
    conveyor_id BIGINT NOT NULL,
    master_shikake_id BIGINT NOT NULL,
    shikake_type VARCHAR(20) NULL,
    
    defect_date DATE NOT NULL,
    shift INT NOT NULL,
    qty_defect INT NOT NULL,
    ...
    
    FOREIGN KEY (master_shikake_id) REFERENCES master_shikake(id)
);
```

#### Alasan Perubahan
1. **Konsistensi dengan `kanban_balance`** - Mengikuti pola pemisahan yang sama
2. **History lebih mudah di-filter** - Halaman history sekarang punya toggle Circuit/Shikake
3. **Summary terpisah** - API summary mengembalikan data circuit dan shikake secara terpisah

---

## 2. Perubahan Identifier Circuit

### 2.1 Dari `cct_no + cct_code` ke `master_circuit_id`

#### Rencana Awal
```php
// KanbanGeneratorService
$balance = KanbanBalance::findOrCreateForCircuit(
    $conveyorId,
    $circuitData['cct_no'],      // String
    $circuitData['cct_code']     // String
);

// DefectService
$balance = KanbanBalance::where([
    'cct_no' => $params['cct_no'],
    'cct_code' => $params['cct_code'],
])->first();
```

#### Implementasi Aktual
```php
// KanbanGeneratorService
$balance = KanbanBalanceCircuit::findOrCreate(
    $conveyorId,
    $circuitData['master_circuit_id']  // Integer FK
);

// DefectService
$balance = KanbanBalanceCircuit::where([
    'master_circuit_id' => $params['master_circuit_id'],
])->first();
```

#### Alasan Perubahan
1. **Referential Integrity** - Foreign key memastikan data valid
2. **Single source of truth** - Tidak ada redundansi `cct_no`/`cct_code`
3. **Performance** - Join dengan `master_circuit` lebih efisien dengan integer FK
4. **Maintenance** - Jika `cct_no`/`cct_code` berubah di master, tidak perlu update banyak tabel

---

## 3. Perubahan E-Kanban Service

### 3.1 Base Query - Main Table

#### Rencana Awal
```php
// Query dari assy_schedule sebagai main table
$query = AssySchedule::query()
    ->leftJoin('assy_schedule_circuit', ...)
    ->where('assy_schedule.is_lock', 1);
```

#### Implementasi Aktual
```php
// Query dari assy_schedule_circuit sebagai main table
$query = DB::table('assy_schedule_circuit')
    ->join('assy_schedule', ...)  // INNER JOIN
    ->where('assy_schedule.is_lock', 1)
    ->whereNotNull('assy_schedule_circuit.barcode_kanban');
```

#### Alasan Perubahan
1. **Hanya tampilkan yang sudah di-generate** - `INNER JOIN` + `whereNotNull(barcode_kanban)`
2. **Counting lebih akurat** - `COUNT(*)` langsung dari tabel kanban
3. **Group by lebih sederhana** - Group by `assy_schedule_id + master_circuit_id`

---

### 3.2 Group ID Format

#### Rencana Awal
```php
// Format: {assyScheduleId}-{cctNo}-{cctCode}
$groupId = "{$row->assy_schedule_id}-{$row->cct_no}-{$row->cct_code}";
```

#### Implementasi Aktual
```php
// Format: {assyScheduleId}-{masterCircuitId}
$groupId = "{$row->assy_schedule_id}-{$row->master_circuit_id}";
```

#### Alasan Perubahan
1. **Konsisten dengan identifier baru** - Menggunakan `master_circuit_id`
2. **Lebih pendek** - Tidak ada karakter khusus dari `cct_no`/`cct_code`
3. **Reliable** - Integer ID tidak akan berubah

---

### 3.3 Issue Count Calculation

#### Rencana Awal
```php
// Counting dengan DISTINCT
->selectRaw('COUNT(DISTINCT master_circuit.id) as issue_count')
```

#### Implementasi Aktual
```php
// Counting langsung dari rows
->selectRaw('COUNT(*) as issue_count')
```

#### Alasan Perubahan
- **1 row = 1 kanban** - Setiap row di `assy_schedule_circuit` adalah 1 kanban yang di-generate
- `COUNT(*)` sudah cukup karena kita group by `assy_schedule_id + master_circuit_id`

---

### 3.4 Print Status Filter

#### Rencana Awal
```php
// Filter berdasarkan is_printed column
->where('is_printed', $printed)
```

#### Implementasi Aktual
```php
// Filter menggunakan HAVING setelah GROUP BY
->havingRaw('MIN(assy_schedule_circuit.is_printed) = ?', [$printed ? 1 : 0])
```

#### Alasan Perubahan
- **Group consideration** - Semua kanban dalam group harus sudah di-print (MIN = 1) atau belum (MIN = 0)
- Tidak bisa filter individual row karena output di-group

---

### 3.5 Mark as Printed

#### Rencana Awal
```php
// Update single record
AssyScheduleCircuit::where('id', $id)->update(['is_printed' => true]);
```

#### Implementasi Aktual
```php
// Update ALL rows in group
AssyScheduleCircuit::where('assy_schedule_id', $assyScheduleId)
    ->where('master_circuit_id', $masterCircuitId)
    ->update([
        'is_printed' => true,
        'print_count' => DB::raw('print_count + 1'),
        'last_printed_at' => now(),
    ]);
```

#### Alasan Perubahan
- **Partial print tidak diizinkan** - Semua kanban dalam 1 group harus di-print bersamaan
- Print count di-increment untuk tracking

---

## 4. Perubahan Defect Feature

### 4.1 History dengan Type Filter

#### Rencana Awal
```php
// Single query dengan type filter
$history = DefectLog::where('type', $type)->paginate();
```

#### Implementasi Aktual
```php
// Separate methods per type
public function getCircuitDefectHistory($filters) {
    return DefectLogCircuit::with(['masterCircuit', ...])->paginate();
}

public function getShikakeDefectHistory($filters) {
    return DefectLogShikake::with(['masterShikake', ...])->paginate();
}
```

#### Alasan Perubahan
- **UI Toggle** - Halaman history punya toggle Circuit/Shikake
- **Query lebih optimal** - Tidak ada discriminator `type`
- **Eager loading berbeda** - Circuit load `masterCircuit`, Shikake load `masterShikake`

---

### 4.2 Summary API

#### Rencana Awal
```php
// Single summary dengan groupBy type
return [
    'total_qty' => $total,
    'by_type' => DefectLog::groupBy('type')->get(),
];
```

#### Implementasi Aktual
```php
// Separate summaries
return [
    'circuit' => $this->getCircuitDefectSummary($dateFrom, $dateTo, $conveyorId),
    'shikake' => $this->getShikakeDefectSummary($dateFrom, $dateTo, $conveyorId),
    'total' => [
        'total_qty' => $circuitSummary['total_qty'] + $shikakeSummary['total_qty'],
        'total_count' => $circuitSummary['total_count'] + $shikakeSummary['total_count'],
    ],
];
```

---

## 5. Constraint yang Dihapus

### 5.1 `unique_circuit_group` Constraint

#### Rencana Awal
```sql
-- Di assy_schedule_circuit
UNIQUE KEY unique_circuit_group (assy_schedule_id, cct_no, cct_code);
```

#### Implementasi Aktual
```php
// Migration untuk drop constraint
Schema::table('assy_schedule_circuit', function ($table) {
    $table->dropUnique('unique_circuit_group');
});
```

#### Alasan Perubahan
- **Multi-issue per group** - 1 schedule + 1 circuit bisa punya banyak kanban (issue 001/005, 002/005, dst)
- Constraint ini menyebabkan error "Duplicate entry" saat generate

---

## 6. File-File yang Diubah

### Models (Baru)
| File | Keterangan |
|------|------------|
| `KanbanBalanceCircuit.php` | ✅ Baru - menggantikan sebagian `KanbanBalance` |
| `KanbanBalanceShikake.php` | ✅ Baru - menggantikan sebagian `KanbanBalance` |
| `DefectLogCircuit.php` | ✅ Baru - menggantikan sebagian `DefectLog` |
| `DefectLogShikake.php` | ✅ Baru - menggantikan sebagian `DefectLog` |

### Models (Dihapus)
| File | Keterangan |
|------|------------|
| `KanbanBalance.php` | ❌ Dihapus - digantikan 2 model terpisah |
| `DefectLog.php` | ❌ Dihapus - digantikan 2 model terpisah |

### Services (Diubah)
| File | Perubahan |
|------|-----------|
| `KanbanGeneratorService.php` | Import baru, method `findOrCreate()` |
| `DefectService.php` | Semua method direfactor untuk 2 tabel |
| `EkanbanCircuitService.php` | Base query, group ID, print status |
| `EkanbanShikakeService.php` | Base query, group ID, print status |

### Controllers (Diubah)
| File | Perubahan |
|------|-----------|
| `DefectController.php` | Gunakan `master_circuit_id`, history dengan type filter |

### Views (Diubah)
| File | Perubahan |
|------|-----------|
| `defect/history.blade.php` | Toggle Circuit/Shikake, shikake_type filter |

### Migrations (Baru)
| File | Keterangan |
|------|------------|
| `2026_01_25_000001_drop_unique_circuit_group_constraint.php` | Drop constraint |
| `2026_01_27_000001_separate_kanban_balance_tables.php` | Split kanban_balance |
| `2026_01_27_000002_separate_defect_log_tables.php` | Split defect_log |

---

## 7. Langkah Selanjutnya

Setelah semua perubahan di atas, jalankan:

```bash
php artisan migrate
```

Ini akan:
1. Drop constraint `unique_circuit_group`
2. Membuat tabel `kanban_balance_circuit` dan `kanban_balance_shikake`
3. Migrasi data dari `kanban_balance` ke tabel baru (jika ada)
4. Drop tabel `kanban_balance` lama
5. Membuat tabel `defect_log_circuit` dan `defect_log_shikake`
6. Drop tabel `defect_log` lama

---

## 8. Kesimpulan

Perubahan utama dari rencana awal adalah **pemisahan tabel yang sebelumnya unified dengan discriminator `type`** menjadi **tabel-tabel terpisah dengan proper foreign key**. Hal ini memberikan:

1. ✅ **Struktur database lebih bersih** - Tidak ada nullable columns
2. ✅ **Referential integrity** - Foreign key ke master tables
3. ✅ **Query lebih efisien** - Tidak perlu filter by type
4. ✅ **Maintenance lebih mudah** - Perubahan di master otomatis terefleksi
5. ✅ **UI lebih jelas** - Toggle terpisah untuk Circuit vs Shikake

---

*Dokumen dibuat: 27 Januari 2026*
