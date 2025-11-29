<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterArea extends Model
{
    use SoftDeletes;

    protected $table = 'master_area';

    protected $fillable = [
        'area',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
