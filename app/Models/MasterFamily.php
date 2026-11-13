<?php

namespace App\Models;

use App\Models\Concerns\RestrictedByArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterFamily extends Model
{
    use SoftDeletes, RestrictedByArea;

    protected $table = 'master_family';

    /** Restricted directly by the master_area_id column. */
    protected $areaColumn = 'master_area_id';

    protected $fillable = [
        'family',
        'master_area_id',
        'carline_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Families already confirmed to exist during this request, keyed by
     * "{area}|{family}", so bulk imports do not look up the same family per row.
     *
     * @var array<string, true>
     */
    protected static array $ensured = [];

    /**
     * Get the area this family belongs to.
     */
    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'master_area_id');
    }

    /**
     * Get the carline that owns the family (optional extra info).
     */
    public function carline()
    {
        return $this->belongsTo(MasterCarline::class, 'carline_id');
    }

    /**
     * Make sure a family used by Circuit/Shikake data is registered in master
     * family (same idea as master machine). The area comes from the data's
     * conveyor. An existing family is left untouched; a soft-deleted one is
     * brought back because the data still uses it.
     */
    public static function ensureForConveyor(?string $family, $conveyorId): void
    {
        $family = trim((string) $family);
        if ($family === '' || !$conveyorId) {
            return;
        }

        $areaId = DB::table('master_conveyor')
            ->where('id', $conveyorId)
            ->value('master_area_id');
        if (!$areaId) {
            return;
        }

        $key = $areaId . '|' . mb_strtolower($family);
        if (isset(static::$ensured[$key])) {
            return;
        }

        // withoutGlobalScopes: the row must be found even when it is outside the
        // logged-in user's area restriction or soft-deleted.
        $existing = static::withoutGlobalScopes()
            ->where('family', $family)
            ->where('master_area_id', $areaId)
            ->orderByRaw('deleted_at is not null')
            ->first();

        if (!$existing) {
            static::withoutGlobalScopes()->create([
                'family' => $family,
                'master_area_id' => $areaId,
                'created_by' => Auth::id(),
            ]);
        } elseif ($existing->trashed()) {
            $existing->restore();
            $existing->forceFill(['deleted_by' => null])->save();
        }

        static::$ensured[$key] = true;
    }

    /**
     * Forget the per-request cache (after a family is deleted/renamed).
     */
    public static function forgetEnsured(): void
    {
        static::$ensured = [];
    }
}
