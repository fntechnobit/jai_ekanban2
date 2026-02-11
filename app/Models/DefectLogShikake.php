<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectLogShikake extends Model
{
    protected $table = 'defect_log_shikake';
    
    protected $fillable = [
        'conveyor_id',
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
     * Get the master shikake that owns this defect log
     */
    public function masterShikake(): BelongsTo
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
     * Scope for specific shikake type
     */
    public function scopeOfType($query, string $shikakeType)
    {
        return $query->where('shikake_type', $shikakeType);
    }
}
