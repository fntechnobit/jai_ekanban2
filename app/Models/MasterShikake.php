<?php

namespace App\Models;

use App\Models\Concerns\RestrictedByArea;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterShikake extends Model
{
    use HasFactory, SoftDeletes, RestrictedByArea;

    protected $table = 'master_shikake';

    /** Restricted through its conveyor, which is itself area-scoped. */
    protected $areaRelation = 'conveyor';

    protected $fillable = [
        'conveyor_id',
        'process',
        'carline',
        'conveyor',
        'machine',
        'qty',
        'family',
        'sequence',
        'released_note',
        'image_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'sequence' => 'integer',
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
     * Get the twist records associated with this shikake
     */
    public function twists()
    {
        return $this->hasMany(MasterShikakeTwist::class, 'master_shikake_id');
    }

    /**
     * Get the single twist record associated with this shikake
     */
    public function twistData()
    {
        return $this->hasOne(MasterShikakeTwist::class, 'master_shikake_id');
    }

    /**
     * Get the bonder records associated with this shikake
     */
    public function bonders()
    {
        return $this->hasMany(MasterShikakeBonder::class, 'master_shikake_id');
    }

    /**
     * Get the single bonder record associated with this shikake
     */
    public function bonderData()
    {
        return $this->hasOne(MasterShikakeBonder::class, 'master_shikake_id');
    }

    /**
     * Get the joint records associated with this shikake
     */
    public function joints()
    {
        return $this->hasMany(MasterShikakeJoint::class, 'master_shikake_id');
    }

    /**
     * Get the single joint record associated with this shikake
     */
    public function jointData()
    {
        return $this->hasOne(MasterShikakeJoint::class, 'master_shikake_id');
    }

    /**
     * Get the shield records associated with this shikake
     */
    public function shields()
    {
        return $this->hasMany(MasterShikakeShield::class, 'master_shikake_id');
    }

    /**
     * Get the single shield record associated with this shikake
     */
    public function shieldData()
    {
        return $this->hasOne(MasterShikakeShield::class, 'master_shikake_id');
    }

    /**
     * Get the dbl crimp records associated with this shikake
     */
    public function dblCrimps()
    {
        return $this->hasMany(MasterShikakeDblCrimp::class, 'master_shikake_id');
    }

    /**
     * Get the single dbl crimp record associated with this shikake
     */
    public function dblCrimpData()
    {
        return $this->hasOne(MasterShikakeDblCrimp::class, 'master_shikake_id');
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

    /**
     * Resolve the type-specific "kode shikake" for a row that carries the
     * child-table identifier columns (twist_cct_no, bonder_no, joint_bonder_no,
     * shield_no, dbl_crimp_drawing_no) alongside `process`, e.g. via the
     * left joins used by getIdentifierSelectColumns()/getIdentifierJoins().
     */
    public static function resolveIdentifier($row): string
    {
        return match ($row->process ?? null) {
            'TWIST' => $row->twist_cct_no ?? '-',
            'BONDER' => $row->bonder_no ?? '-',
            'JOINT' => $row->joint_bonder_no ?? '-',
            'SHIELD' => $row->shield_no ?? '-',
            'DBL CRIMP' => $row->dbl_crimp_drawing_no ?? '-',
            default => '-',
        };
    }

    /**
     * Left-join every child table needed to resolve the identifier via resolveIdentifier().
     */
    public static function joinIdentifierTables($query)
    {
        return $query
            ->leftJoin('master_shikake_twist', 'master_shikake.id', '=', 'master_shikake_twist.master_shikake_id')
            ->leftJoin('master_shikake_bonder', 'master_shikake.id', '=', 'master_shikake_bonder.master_shikake_id')
            ->leftJoin('master_shikake_joint', 'master_shikake.id', '=', 'master_shikake_joint.master_shikake_id')
            ->leftJoin('master_shikake_shield', 'master_shikake.id', '=', 'master_shikake_shield.master_shikake_id')
            ->leftJoin('master_shikake_dbl_crimp', 'master_shikake.id', '=', 'master_shikake_dbl_crimp.master_shikake_id');
    }

    /**
     * Select columns (aliased) needed by resolveIdentifier().
     */
    public static function identifierSelectColumns(): array
    {
        return [
            'master_shikake_twist.cct_no as twist_cct_no',
            'master_shikake_bonder.bonder_no',
            'master_shikake_joint.bonder_no as joint_bonder_no',
            'master_shikake_shield.shield_no',
            'master_shikake_dbl_crimp.drawing_no as dbl_crimp_drawing_no',
        ];
    }
}
