<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssySchedule extends Model
{
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
        'transferred_from_date',
        'transferred_from_shift',
        'transferred_from_cutoff',
        'transferred_from_listing_id',
        'is_lock',
        'verified_at',
        'verified_by',
        'created_by',
        'updated_by',
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
        'transferred_from_date' => 'date',
        'transferred_from_shift' => 'integer',
        'transferred_from_cutoff' => 'integer',
        'transferred_from_listing_id' => 'integer',
        'is_lock' => 'boolean',
        'verified_at' => 'datetime',
        'verified_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
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
