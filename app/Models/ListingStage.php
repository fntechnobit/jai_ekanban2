<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingStage extends Model
{
    protected $table = 'listing_stage';

    protected $fillable = [
        'id_listing',
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
        'snp',
        'snpa',
        'synced_at',
    ];

    protected $casts = [
        'listing_date_time' => 'datetime',
        'synced_at' => 'datetime',
        'shift' => 'integer',
        'qty' => 'integer',
        'seq' => 'integer',
        'plt' => 'integer',
        'mode' => 'integer',
        'snp' => 'integer',
        'snpa' => 'integer',
    ];
}
