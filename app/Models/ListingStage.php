<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListingStage extends Model
{
    /**
     * Listing yang diinput langsung di e-Kanban (menu Listing Manual), bukan dari SIREP.
     * Sinkronisasi SIREP tidak pernah memperbarui maupun menghapus baris ini.
     */
    public const SOURCE_MANUAL = 'manual';

    protected $table = 'listing_stage';

    protected $fillable = [
        'id_listing',
        'source',
        'listing_date_time',
        'conveyor',
        'shift',
        'assycode',
        'assy',
        'carline',
        'qty',
        'seq',
        'plt',
        'mode',
        'is_overtime',
        'snp',
        'snpa',
        'remark',
        'synced_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'listing_date_time' => 'datetime',
        'synced_at' => 'datetime',
        'shift' => 'integer',
        'qty' => 'integer',
        'seq' => 'integer',
        'plt' => 'integer',
        'mode' => 'integer',
        'is_overtime' => 'boolean',
        'snp' => 'integer',
        'snpa' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('listing_stage.source', self::SOURCE_MANUAL);
    }

    /**
     * Baris milik SIREP (api / db lama) — satu-satunya yang boleh disentuh sinkronisasi.
     */
    public function scopeFromSirep(Builder $query): Builder
    {
        return $query->where('listing_stage.source', '!=', self::SOURCE_MANUAL);
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function schedules()
    {
        return $this->hasMany(AssySchedule::class, 'listing_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
