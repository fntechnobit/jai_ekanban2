<?php

namespace App\Models;

use App\Models\Concerns\RestrictedByArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterArea extends Model
{
    use SoftDeletes, RestrictedByArea;

    protected $table = 'master_area';

    /** Area restriction reaches the area via its own primary key. */
    protected $areaColumn = 'id';

    protected $fillable = [
        'area',
        'cutoff_print_rule',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'cutoff_print_rule' => 'boolean',
    ];

    /**
     * Get the carlines for the area.
     */
    public function carlines()
    {
        return $this->hasMany(MasterCarline::class, 'area_id');
    }
}
