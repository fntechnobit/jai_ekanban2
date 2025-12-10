<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterConveyor extends Model
{
    use SoftDeletes;

    protected $table = 'master_conveyor';

    protected $fillable = [
        'master_area_id',
        'conveyor',
        'shift_start',
        'shift_qty',
        'capacity',
        'pallet_qty',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

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
