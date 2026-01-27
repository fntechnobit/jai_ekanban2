<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanBalanceShikake extends Model
{
    protected $table = 'kanban_balance_shikake';
    
    protected $fillable = [
        'conveyor_id',
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
     * Get the master shikake that owns this balance
     */
    public function masterShikake(): BelongsTo
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
     * Find or create balance for shikake
     */
    public static function findOrCreate(int $conveyorId, int $masterShikakeId): self
    {
        return static::firstOrCreate(
            [
                'conveyor_id' => $conveyorId,
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
