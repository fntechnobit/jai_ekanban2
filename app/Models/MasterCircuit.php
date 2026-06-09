<?php

namespace App\Models;

use App\Models\Concerns\RestrictedByArea;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCircuit extends Model
{
    use HasFactory, SoftDeletes, RestrictedByArea;

    protected $table = 'master_circuit';

    /** Restricted through its conveyor, which is itself area-scoped. */
    protected $areaRelation = 'conveyor';

    protected $fillable = [
        'conveyor_id',
        'type',
        'carline',
        'conveyor',
        'cct_no',
        'family',
        'qty',
        'machine',
        'machine_twist',
        'memory_twist',
        'sequence',
        'sequence_2',
        'released_note',
        'cust_no',
        'barcode_mesin',
        'barcode_navigasi',
        'barcode_process',
        'barcode_shikake',
        'barcode_twist',
        'qrcode_drawing',
        'to_store',
        'address',
        'cct_code',
        'shikake_code',
        'kind',
        'size',
        'col',
        'cl',
        'terminal_1',
        'note_1',
        'gold_1',
        'strip_1',
        'acc_1',
        'acc_1a',
        'tube_1',
        'mark_1',
        'terminal_2',
        'note_2',
        'gold_2',
        'strip_2',
        'acc_2',
        'acc_2a',
        'tube_2',
        'mark_2',
        'ta',
        'tb',
        't01',
        't02',
        't03',
        'image_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the conveyor that owns this circuit
     */
    public function conveyor()
    {
        return $this->belongsTo(MasterConveyor::class, 'conveyor_id');
    }

    /**
     * Get the assemblies associated with this circuit through pivot table
     */
    public function assemblies()
    {
        return $this->belongsToMany(MasterAssy::class, 'master_circuit_assy', 'master_circuit_id', 'master_assy_id');
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
