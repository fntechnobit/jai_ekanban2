<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShikakeBonder extends Model
{
    use HasFactory;

    protected $table = 'master_shikake_bonder';

    protected $fillable = [
        'master_shikake_id',
        'bonder_no',
        'address',
        'dies',
        'to_machine',
        'barcode_navigasi',
        'barcode_process',
        'qrcode_drawing',
        'cct_no_a_1',
        'bonder_no_a_1',
        'cct_no_a_2',
        'bonder_no_a_2',
        'cct_no_a_3',
        'bonder_no_a_3',
        'cct_no_a_4',
        'bonder_no_a_4',
        'cct_no_a_5',
        'bonder_no_a_5',
        'cct_no_a_6',
        'bonder_no_a_6',
        'cct_no_a_7',
        'bonder_no_a_7',
        'cct_no_b_1',
        'bonder_no_b_1',
        'cct_no_b_2',
        'bonder_no_b_2',
        'cct_no_b_3',
        'bonder_no_b_3',
        'cct_no_b_4',
        'bonder_no_b_4',
        'cct_no_b_5',
        'bonder_no_b_5',
        'cct_no_b_6',
        'bonder_no_b_6',
        'cct_no_b_7',
        'bonder_no_b_7',
    ];

    protected $casts = [
        //
    ];

    /**
     * Get the master shikake that owns this bonder record
     */
    public function masterShikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
