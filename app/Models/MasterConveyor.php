<?php

namespace App\Models;

use App\Models\Concerns\RestrictedByArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterConveyor extends Model
{
    use SoftDeletes, RestrictedByArea;

    protected $table = 'master_conveyor';

    /** Restricted directly by the master_area_id column. */
    protected $areaColumn = 'master_area_id';

    /**
     * `capacity`, `overtime_capacity` dan `capacity_synced_at` sengaja TIDAK ada di
     * sini: ketiganya milik SIREP dan hanya boleh ditulis `sirep:sync-conveyor`,
     * bukan lewat form master. `sirep_conveyor_code` tetap dapat diisi manual —
     * itulah jembatan bagi conveyor yang namanya berbeda antara SIREP dan sini.
     */
    protected $fillable = [
        'master_area_id',
        'sirep_conveyor_code',
        'pallet_qty',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'capacity_synced_at' => 'datetime',
        'deactivated_at'     => 'datetime',
        'is_active'          => 'boolean',
    ];

    /**
     * Conveyor yang masih ada di SIREP. Hanya conveyor inilah yang boleh
     * dijadwalkan, diverifikasi, dan dicetak kanbannya.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Siap dijadwalkan: masih di SIREP dan kapasitasnya sudah tersinkron. */
    public function isSchedulable(): bool
    {
        return (bool) $this->is_active && $this->hasSyncedCapacity();
    }

    /** Kapasitas siap pakai hanya bila SIREP sudah pernah mengirimkannya. */
    public function hasSyncedCapacity(): bool
    {
        return (int) ($this->capacity ?? 0) > 0;
    }

    /** Nama yang dipakai mencocokkan conveyor ini dengan `name` dari API SIREP. */
    public function sirepName(): string
    {
        return trim((string) ($this->sirep_conveyor_code ?: $this->conveyor));
    }

    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'master_area_id');
    }

    public function familyConveyors()
    {
        return $this->hasMany(MasterFamilyConveyor::class, 'conveyor_id');
    }

    public function families()
    {
        return $this->belongsToMany(MasterFamily::class, 'master_family_conveyor', 'conveyor_id', 'family_id');
    }

    /**
     * Get the machines associated with this conveyor
     */
    public function machines()
    {
        return $this->belongsToMany(MasterMachine::class, 'master_machine_conveyor', 'conveyor_id', 'machine_id');
    }

    /**
     * Get the machine conveyor pivot records
     */
    public function machineConveyors()
    {
        return $this->hasMany(MasterMachineConveyor::class, 'conveyor_id');
    }
}
