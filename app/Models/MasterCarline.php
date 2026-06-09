<?php

namespace App\Models;

use App\Models\Concerns\RestrictedByArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCarline extends Model
{
    use SoftDeletes, RestrictedByArea;

    protected $table = 'master_carline';

    /** Restricted directly by the area_id column. */
    protected $areaColumn = 'area_id';

    protected $fillable = [
        'code',
        'name',
        'area_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the area that owns the carline.
     */
    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'area_id');
    }

    /**
     * Get the families for the carline.
     */
    public function families()
    {
        return $this->hasMany(MasterFamily::class, 'carline_id');
    }
}
