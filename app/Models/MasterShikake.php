<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterShikake extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_shikake';

    protected $fillable = [
        'conveyor_id',
        'conveyor',
        'shikake_no',
        'family',
        'qty',
        'issue',
        'machine',
        'sequence',
        'barcode_kanban',
        'released_date',
        'released_note',
        'store',
        'barcode_mesin',
        'address',
        'cct_a',
        'address_a',
        'cct_b',
        'address_b',
        'cct_c',
        'address_c',
        'cct_4',
        'address_4',
        'cct_5',
        'address_5',
        'cct_6',
        'address_6',
        'cct_7',
        'address_7',
        'barcode_proses',
        'barcode_navigasi',
        'dies',
        'jumlah_kombinasi',
        'blade',
        't01',
        't02',
        't03',
        't04',
        't05',
        't06',
        't07',
        't08',
        't09',
        'joint',
        'image_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'released_date' => 'date',
        'qty' => 'integer',
        'sequence' => 'integer',
        'jumlah_kombinasi' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the conveyor that owns this shikake
     */
    public function conveyor()
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }

    /**
     * Get the assemblies associated with this shikake through pivot table
     */
    public function assemblies()
    {
        return $this->belongsToMany(MasterAssy::class, 'master_shikake_assy', 'master_shikake_id', 'master_assy_id');
    }

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
