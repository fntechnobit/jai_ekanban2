<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssySchedule extends Model
{
    use SoftDeletes;

    protected $table = 'assy_schedule';

    protected $fillable = [
        'schedule',
        'conveyor_id',
        'listing_id',
        'shift',
        'assycode',
        'assy',
        'qty',
        'seq',
        'plt',
        'mode',
        'snp',
        'snpa',
        'cutoff',
        'is_lock',
        'verified_at',
        'verified_by',
        'created_by',
        'updated_by',
        'is_user_edited',
    ];

    protected $casts = [
        'schedule' => 'datetime',
        'conveyor_id' => 'integer',
        'listing_id' => 'integer',
        'shift' => 'integer',
        'qty' => 'integer',
        'seq' => 'integer',
        'plt' => 'integer',
        'mode' => 'integer',
        'snp' => 'integer',
        'snpa' => 'integer',
        'cutoff' => 'integer',
        'is_lock' => 'boolean',
        'verified_at' => 'datetime',
        'verified_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'is_user_edited' => 'boolean',
    ];

    /**
     * Get the conveyor that owns the schedule
     */
    public function conveyor()
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }

    /**
     * Get the listing stage that owns the schedule
     */
    public function listingStage()
    {
        return $this->belongsTo(ListingStage::class, 'listing_id');
    }

    /**
     * Get the user who created the record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who verified the schedule
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
