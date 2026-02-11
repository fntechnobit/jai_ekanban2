<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterAssy extends Model
{
    use HasFactory;

    protected $table = 'master_assy';

    protected $fillable = [
        'assy',
    ];

    /**
     * Get the circuits associated with this assy through pivot table
     */
    public function circuits()
    {
        return $this->belongsToMany(MasterCircuit::class, 'master_circuit_assy', 'master_assy_id', 'master_circuit_id');
    }

    /**
     * Get the shikakes associated with this assy through pivot table
     */
    public function shikakes()
    {
        return $this->belongsToMany(MasterShikake::class, 'master_shikake_assy', 'master_assy_id', 'master_shikake_id');
    }
}
