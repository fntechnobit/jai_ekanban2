<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceResetSnapshot extends Model
{
    protected $table = 'balance_reset_snapshot';

    protected $fillable = [
        'reset_log_id', 'item_type', 'conveyor_id', 'master_id',
        'sisa_before', 'sisa_after', 'nomor_urut_before', 'nomor_urut_after',
    ];

    public function resetLog(): BelongsTo
    {
        return $this->belongsTo(BalanceResetLog::class, 'reset_log_id');
    }
}
