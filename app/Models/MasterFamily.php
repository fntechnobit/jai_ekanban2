<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterFamily extends Model
{
    use SoftDeletes;

    protected $table = 'master_family';

    protected $fillable = [
        'family',
        'carline_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the carline that owns the family.
     */
    public function carline()
    {
        return $this->belongsTo(MasterCarline::class, 'carline_id');
    }
}
