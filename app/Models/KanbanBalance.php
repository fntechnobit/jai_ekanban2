<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanBalance extends Model
{
    protected $table = 'kanban_balance';
    
    protected $fillable = [
        'conveyor_id',
        'type',
        'cct_no',
        'cct_code',
        'master_shikake_id',
        'sisa',
        'last_nomor_urut',
        'last_schedule_id',
        'last_schedule_date',
        'last_shift',
    ];
    
    protected $casts = [
        'sisa' => 'integer',
        'last_nomor_urut' => 'integer',
        'last_shift' => 'integer',
        'last_schedule_date' => 'date',
    ];
    
    /**
     * Get the conveyor that owns this balance
     */
    public function conveyor(): BelongsTo
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }
    
    /**
     * Get the shikake that owns this balance (for shikake type)
     */
    public function shikake(): BelongsTo
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
    
    /**
     * Get the last schedule that updated this balance
     */
    public function lastSchedule(): BelongsTo
    {
        return $this->belongsTo(AssySchedule::class, 'last_schedule_id');
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
     * Find or create balance for circuit
     */
    public static function findOrCreateForCircuit(int $conveyorId, string $cctNo, string $cctCode): self
    {
        return static::firstOrCreate(
            [
                'conveyor_id' => $conveyorId,
                'type' => 'circuit',
                'cct_no' => $cctNo,
                'cct_code' => $cctCode,
            ],
            [
                'sisa' => 0,
                'last_nomor_urut' => 0,
            ]
        );
    }
    
    /**
     * Find or create balance for shikake
     */
    public static function findOrCreateForShikake(int $conveyorId, int $masterShikakeId): self
    {
        return static::firstOrCreate(
            [
                'conveyor_id' => $conveyorId,
                'type' => 'shikake',
                'master_shikake_id' => $masterShikakeId,
            ],
            [
                'sisa' => 0,
                'last_nomor_urut' => 0,
            ]
        );
    }
    
    /**
     * Reduce balance (for defect)
     */
    public function reduceBalance(int $amount): bool
    {
        if ($amount > $this->sisa) {
            return false;
        }
        
        $this->sisa -= $amount;
        return $this->save();
    }
    
    /**
     * Update balance after kanban generation
     */
    public function updateAfterGeneration(int $sisa, int $lastNomorUrut, int $scheduleId, string $scheduleDate, int $shift): bool
    {
        $this->sisa = $sisa;
        $this->last_nomor_urut = $lastNomorUrut;
        $this->last_schedule_id = $scheduleId;
        $this->last_schedule_date = $scheduleDate;
        $this->last_shift = $shift;
        
        return $this->save();
    }
}
