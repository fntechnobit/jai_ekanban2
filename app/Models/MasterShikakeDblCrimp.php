<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShikakeDblCrimp extends Model
{
    use HasFactory;

    protected $table = 'master_shikake_dbl_crimp';

    protected $fillable = [
        'master_shikake_id',
        'drawing_no',
        'address',
        'barcode_mesin',
        'to_machine',
        'qrcode_drawing',
        'cct_no_1',
        'address_1',
        'cct_no_2',
        'address_2',
        'cct_no_3',
        'address_3',
        'cct_no_4',
        'address_4',
        'cct_no_5',
        'address_5',
    ];

    /**
     * Get the master shikake that owns this dbl crimp record
     */
    public function masterShikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
