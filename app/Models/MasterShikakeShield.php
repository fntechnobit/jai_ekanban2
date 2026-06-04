<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShikakeShield extends Model
{
    use HasFactory;

    protected $table = 'master_shikake_shield';

    protected $fillable = [
        'master_shikake_id',
        'shield_no',
        'address',
        'blade',
        'to_machine',
        'qrcode_drawing',
        'cct_no_1',
        'address_no_1_1',
        'cct_no_2',
        'address_no_1_2',
        'to_1',
        'to_2',
        'to_3',
        'to_4',
        'to_5',
        'to_6',
        'to_7',
        'to_8',
        'to_9',
    ];

    /**
     * Get the master shikake that owns this shield record
     */
    public function masterShikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
