<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMachineConveyor extends Model
{
    use HasFactory;

    protected $table = 'master_machine_conveyor';

    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'conveyor_id',
    ];

    /**
     * Get the machine that owns this record
     */
    public function machine()
    {
        return $this->belongsTo(MasterMachine::class, 'machine_id');
    }

    /**
     * Get the conveyor that owns this record
     */
    public function conveyor()
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }
}
