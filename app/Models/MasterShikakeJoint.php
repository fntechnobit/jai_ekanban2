<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShikakeJoint extends Model
{
    use HasFactory;

    protected $table = 'master_shikake_joint';

    protected $fillable = [
        'master_shikake_id',
        'bonder_no',
        'address',
        'address_store',
        'to_machine',
        'barcode_process',
        'released_date',
        'cct_no_1',
        'bonder_no_1',
        'cct_no_2',
        'bonder_no_2',
        'cct_no_3',
        'bonder_no_3',
        'cct_no_4',
        'bonder_no_4',
        'cct_no_5',
        'bonder_no_5',
    ];

    protected $casts = [
        'released_date' => 'date',
    ];

    /**
     * Get the master shikake that owns this joint record
     */
    public function masterShikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
