<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionLogCircuit extends Model
{
    protected $table = 'addition_log_circuit';
    
    protected $fillable = [
        'conveyor_id',
        'master_circuit_id',
        'addition_date',
        'shift',
        'qty_addition',
        'balance_before',
        'balance_after',
        'reason',
        'created_by',
    ];
    
    protected $casts = [
        'addition_date' => 'date',
        'shift' => 'integer',
        'qty_addition' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];
    
    public function conveyor(): BelongsTo
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }
    
    public function masterCircuit(): BelongsTo
    {
        return $this->belongsTo(MasterCircuit::class, 'master_circuit_id');
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('addition_date', $date);
    }
    
    public function scopeInShift($query, int $shift)
    {
        return $query->where('shift', $shift);
    }
    
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('addition_date', [$startDate, $endDate]);
    }
}
