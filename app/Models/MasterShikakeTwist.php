<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShikakeTwist extends Model
{
    use HasFactory;

    protected $table = 'master_shikake_twist';

    protected $fillable = [
        'master_shikake_id',
        'cct_code',
        'cct_no',
        'machine_twist',
        'sequence_2',
        'barcode_navigasi',
        'barcode_process',
        'barcode_shikake',
        'to_store',
        'cust_no',
        'kind',
        'size',
        'color',
        'cl',
        'terminal_a',
        'acc_1_a',
        'tube_a',
        'note_a',
        'strip_a',
        'mark_a',
        'terminal_b',
        'acc_1_ab',
        'tube_b',
        'note_b',
        'strip_b',
        'mark_b',
    ];

    protected $casts = [
        'sequence_2' => 'integer',
    ];

    /**
     * Get the master shikake that owns this twist record
     */
    public function masterShikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
