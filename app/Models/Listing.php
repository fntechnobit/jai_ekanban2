<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_listing';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'listing';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_listing';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cv',
        'time',
        'assycode',
        'assy',
        'level',
        'snp',
        'snpa',
        'plt',
        'qty',
        'seq',
        'carline',
        'remark',
        'noloading',
        'mode',
        'packing',
        'status',
        'shift',
        'id_d030',
        'qty_etd',
        'etd',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'time' => 'date',
        'snp' => 'integer',
        'snpa' => 'integer',
        'plt' => 'integer',
        'qty' => 'integer',
        'seq' => 'integer',
        'shift' => 'integer',
        'id_d030' => 'integer',
        'qty_etd' => 'integer',
    ];
}
