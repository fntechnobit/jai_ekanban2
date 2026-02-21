<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCarline extends Model
{
    use SoftDeletes;

    protected $table = 'master_carline';

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
