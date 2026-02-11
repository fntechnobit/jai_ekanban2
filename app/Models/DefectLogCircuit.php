<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectLogCircuit extends Model
{
    protected $table = 'defect_log_circuit';
    
    protected $fillable = [
        'conveyor_id',
        'master_circuit_id',
        'defect_date',
        'shift',
        'qty_defect',
        'balance_before',
        'balance_after',
        'reason',
        'created_by',
    ];
    
    protected $casts = [
        'defect_date' => 'date',
        'shift' => 'integer',
        'qty_defect' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];
    
    /**
     * Get the conveyor that owns this defect log
     */
    public function conveyor(): BelongsTo
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }
    
    /**
     * Get the master circuit that owns this defect log
     */
    public function masterCircuit(): BelongsTo
    {
        return $this->belongsTo(MasterCircuit::class, 'master_circuit_id');
    }
    
    /**
     * Get the user who created this defect log
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Scope for specific date
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('defect_date', $date);
    }
    
    /**
     * Scope for specific shift
     */
    public function scopeInShift($query, int $shift)
    {
        return $query->where('shift', $shift);
    }
    
    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('defect_date', [$startDate, $endDate]);
    }
}
