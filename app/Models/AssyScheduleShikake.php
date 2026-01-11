<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'is_printed' => 'boolean',
        'last_printed_at' => 'datetime',
        'print_count' => 'integer',
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
}
