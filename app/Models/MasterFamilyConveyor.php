<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterFamilyConveyor extends Model
{
    protected $table = 'master_family_conveyor';

    protected $fillable = [
        'conveyor_id',
        'family_id',
    ];

    public function conveyor()
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }

    public function family()
    {
        return $this->belongsTo(MasterFamily::class, 'family_id');
    }
}
