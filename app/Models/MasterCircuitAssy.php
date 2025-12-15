<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCircuitAssy extends Model
{
    use HasFactory;

    protected $table = 'master_circuit_assy';

    protected $fillable = [
        'master_assy_id',
        'master_circuit_id',
    ];

    /**
     * Get the assembly that owns this pivot record
     */
    public function assy()
    {
        return $this->belongsTo(MasterAssy::class, 'master_assy_id');
    }

    /**
     * Get the circuit that owns this pivot record
     */
    public function circuit()
    {
        return $this->belongsTo(MasterCircuit::class, 'master_circuit_id');
    }
}
