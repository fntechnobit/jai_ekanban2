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
        'shield_no',
        'dbl_crimp',
    ];

    /**
     * Get the master shikake that owns this dbl crimp record
     */
    public function masterShikake()
    {
        return $this->belongsTo(MasterShikake::class, 'master_shikake_id');
    }
}
