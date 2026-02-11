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
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
