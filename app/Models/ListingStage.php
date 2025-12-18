<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingStage extends Model
{
    protected $table = 'listing_stage';

    protected $fillable = [
        'listing_date_time',
        'conveyor',
        'shift',
        'assycode',
        'assy',
        'qty',
        'seq',
        'plt',
        'mode',
        'snp',
        'snpa',
    ];

    protected $casts = [
        'listing_date_time' => 'datetime',
        'shift' => 'integer',
        'qty' => 'integer',
        'seq' => 'integer',
        'plt' => 'integer',
        'mode' => 'integer',
        'snp' => 'integer',
        'snpa' => 'integer',
    ];
}
