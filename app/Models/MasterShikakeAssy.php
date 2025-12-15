<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShikakeAssy extends Model
{
    use HasFactory;

    protected $table = 'master_shikake_assy';

    protected $fillable = [
        'master_assy_id',
        'master_shikake_id',
    ];

    /**
     * Get the assembly that owns this pivot record
     */
    public function assy()
    {
        return $this->belongsTo(MasterAssy::class, 'master_assy_id');
    }

    /**
     * Get the shikake that owns this pivot record
     */
    public function shikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
