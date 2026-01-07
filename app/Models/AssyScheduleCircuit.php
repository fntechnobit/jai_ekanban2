<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssyScheduleCircuit extends Model
{
    protected $table = 'assy_schedule_circuit';
    
    protected $fillable = [
        'assy_schedule_id',
        'cct_no',
        'cct_code',
        'is_printed',
        'last_printed_at',
        'last_printed_by',
        'print_count'
    ];
    
    protected $casts = [
        'is_printed' => 'boolean',
        'last_printed_at' => 'datetime',
        'print_count' => 'integer'
    ];
    
    /**
     * Get the assy schedule this record belongs to
     */
    public function assySchedule()
    {
        return $this->belongsTo(AssySchedule::class, 'assy_schedule_id');
    }
    
    /**
     * Get the user who last printed this circuit group
     */
    public function printedBy()
    {
        return $this->belongsTo(User::class, 'last_printed_by');
    }
}
