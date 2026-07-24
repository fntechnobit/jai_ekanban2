<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Generation ledger entry for the kanban carry-over engine.
 *
 * @property int    $conveyor_id
 * @property string $item_type   'circuit' | 'shikake'
 * @property int    $master_id
 * @property \Carbon\Carbon $schedule_date
 * @property int    $shift
 * @property int    $kanban_count
 * @property int    $lot
 * @property int    $produced
 * @property int    $kebutuhan
 * @property int    $delta
 * @property int    $sisa_before
 * @property int    $sisa_after
 */
class KanbanGenerationLog extends Model
{
    protected $table = 'kanban_generation_log';

    protected $fillable = [
        'conveyor_id',
        'item_type',
        'master_id',
        'schedule_date',
        'shift',
        'kanban_count',
        'lot',
        'produced',
        'kebutuhan',
        'delta',
        'sisa_before',
        'sisa_after',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'shift'         => 'integer',
        'kanban_count'  => 'integer',
        'lot'           => 'integer',
        'produced'      => 'integer',
        'kebutuhan'     => 'integer',
        'delta'         => 'integer',
        'sisa_before'   => 'integer',
        'sisa_after'    => 'integer',
    ];

    public const TYPE_CIRCUIT = 'circuit';
    public const TYPE_SHIKAKE = 'shikake';

    /**
     * Record (or overwrite) the generation entry for a schedule group + item.
     */
    public static function record(
        int $conveyorId,
        string $itemType,
        int $masterId,
        string $scheduleDate,
        int $shift,
        int $kanbanCount,
        int $lot,
        int $kebutuhan,
        int $sisaBefore,
        int $sisaAfter
    ): self {
        $produced = $kanbanCount * $lot;

        return static::updateOrCreate(
            [
                'conveyor_id'   => $conveyorId,
                'item_type'     => $itemType,
                'master_id'     => $masterId,
                'schedule_date' => $scheduleDate,
                'shift'         => $shift,
            ],
            [
                'kanban_count' => $kanbanCount,
                'lot'          => $lot,
                'produced'     => $produced,
                'kebutuhan'    => $kebutuhan,
                'delta'        => $sisaAfter - $sisaBefore,
                'sisa_before'  => $sisaBefore,
                'sisa_after'   => $sisaAfter,
            ]
        );
    }
}
