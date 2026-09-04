<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BalanceResetLog extends Model
{
    protected $table = 'balance_reset_log';

    protected $fillable = [
        'cutoff_date', 'conveyor_ids', 'reference_db',
        'circuits_updated', 'shikakes_updated', 'kanban_deleted', 'schedules_unverified',
        'status', 'note', 'created_by', 'undone_at', 'undone_by',
    ];

    protected $casts = [
        'cutoff_date'  => 'date',
        'conveyor_ids' => 'array',
        'undone_at'    => 'datetime',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(BalanceResetSnapshot::class, 'reset_log_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function undoer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }

    /** Nama conveyor yang ikut disamakan, untuk ditampilkan di riwayat. */
    public function conveyorNames(): string
    {
        $names = MasterConveyor::whereIn('id', $this->conveyor_ids ?? [])
            ->orderBy('conveyor')->pluck('conveyor')->all();

        return empty($names) ? '-' : implode(', ', $names);
    }
}
