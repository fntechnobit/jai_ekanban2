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
        'process',
        'carline',
        'conveyor',
        'machine',
        'qty',
        'family',
        'released_date',
        'sequence',
        'image_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'sequence' => 'integer',
        'released_date' => 'date',
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
}
