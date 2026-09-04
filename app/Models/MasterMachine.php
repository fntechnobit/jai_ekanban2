<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterMachine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_machine';

    protected $fillable = [
        'machine',
        'master_area_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the area this machine belongs to
     */
    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'master_area_id');
    }

    /**
     * Get the conveyors associated with this machine
     */
    public function conveyors()
    {
        return $this->belongsToMany(MasterConveyor::class, 'master_machine_conveyor', 'machine_id', 'conveyor_id');
    }

    /**
     * Get the machine conveyor pivot records
     */
    public function machineConveyors()
    {
        return $this->hasMany(MasterMachineConveyor::class, 'machine_id');
    }
}
