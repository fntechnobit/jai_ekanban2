<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'print_count',
        // Kanban generation fields
        'master_circuit_id',
        'issue',
        'nomor_urut',
        'barcode_kanban',
        'qrcode_shikake',
        'release_date',
        'qty_listing',
        'qty_kanban',
        'cutoff',
    ];
    
    protected $casts = [
        'is_printed' => 'boolean',
        'last_printed_at' => 'datetime',
        'print_count' => 'integer',
        'release_date' => 'date',
        'qty_listing' => 'integer',
        'qty_kanban' => 'integer',
        'cutoff' => 'integer',
        'nomor_urut' => 'integer',
    ];
    
    /**
     * Get the assy schedule this record belongs to
     */
    public function assySchedule(): BelongsTo
    {
        return $this->belongsTo(AssySchedule::class, 'assy_schedule_id');
    }
    
    /**
     * Get the master circuit
     */
    public function masterCircuit(): BelongsTo
    {
        return $this->belongsTo(MasterCircuit::class, 'master_circuit_id');
    }
    
    /**
     * Get the user who last printed this circuit group
     */
    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_printed_by');
    }
    
    /**
     * Format issue as XXX/YYY
     */
    public function getFormattedIssueAttribute(): string
    {
        return $this->issue ?? '';
    }
    
    /**
     * Format nomor urut as 4-digit padded
     */
    public function getFormattedNomorUrutAttribute(): string
    {
        return str_pad($this->nomor_urut ?? 0, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Scope for specific cutoff
     */
    public function scopeCutoff($query, int $cutoff)
    {
        return $query->where('cutoff', $cutoff);
    }
    
    /**
     * Scope for printed
     */
    public function scopePrinted($query)
    {
        return $query->where('is_printed', true);
    }
    
    /**
     * Scope for not printed
     */
    public function scopeNotPrinted($query)
    {
        return $query->where('is_printed', false);
    }
}
