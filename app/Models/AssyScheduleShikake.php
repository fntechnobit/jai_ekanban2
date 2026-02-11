<?php

namespace App\Models;

use App\Enums\ProcessType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssyScheduleShikake extends Model
{
    protected $table = 'assy_schedule_shikake';

    protected $fillable = [
        'assy_schedule_id',
        'master_shikake_id',
        'is_printed',
        'last_printed_at',
        'last_printed_by',
        'print_count',
        // Kanban generation fields
        'issue',
        'nomor_urut',
        'barcode_kanban',
        'release_date',
        'qty_listing',
        'qty_kanban',
        'cutoff',
        'process',
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
        'process' => ProcessType::class,
    ];

    /**
     * Get the assy schedule that owns this print record
     */
    public function assySchedule(): BelongsTo
    {
        return $this->belongsTo(AssySchedule::class, 'assy_schedule_id');
    }

    /**
     * Get the shikake that owns this print record
     */
    public function shikake(): BelongsTo
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }

    /**
     * Get the user who last printed this
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
     * Scope for specific process type
     */
    public function scopeProcess($query, ProcessType $process)
    {
        return $query->where('process', $process->value);
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
