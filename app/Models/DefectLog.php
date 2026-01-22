<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectLog extends Model
{
    protected $table = 'defect_log';
    
    protected $fillable = [
        'conveyor_id',
        'type',
        'cct_no',
        'cct_code',
        'master_shikake_id',
        'shikake_type',
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
     * Get the shikake that owns this defect log (for shikake type)
     */
    public function shikake(): BelongsTo
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
    
    /**
     * Get the user who created this defect log
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Scope for circuit type
     */
    public function scopeCircuit($query)
    {
        return $query->where('type', 'circuit');
    }
    
    /**
     * Scope for shikake type
     */
    public function scopeShikake($query)
    {
        return $query->where('type', 'shikake');
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
    
    /**
     * Get display name for the item
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'circuit') {
            return "CCT: {$this->cct_no} - {$this->cct_code}";
        }
        
        $shikakeCode = $this->shikake?->code ?? $this->master_shikake_id;
        return "Shikake: {$shikakeCode} ({$this->shikake_type})";
    }
}
