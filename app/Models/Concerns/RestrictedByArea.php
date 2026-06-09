<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts a model's queries to the authenticated user's assigned area.
 *
 * When the logged-in user has an `area_id`, every query on the model is
 * automatically scoped to that area (and so are all dropdowns/datatables that
 * read through Eloquent). When the user has no area (NULL = "Semua Area") or no
 * user is authenticated (console, seeders), no restriction is applied.
 *
 * Each model declares HOW it reaches the area:
 *  - protected $areaColumn   = 'area_id';   // a column on the model's own table
 *  - protected $areaRelation = 'conveyor';  // a belongsTo relation that is itself
 *                                           // restricted by this trait
 *
 * Relation-based restrictions compose automatically: `whereHas($relation)` runs
 * the related model's query, which already carries its own area scope.
 */
trait RestrictedByArea
{
    public static function bootRestrictedByArea(): void
    {
        static::addGlobalScope('restrictedByArea', function (Builder $builder) {
            $areaId = static::restrictedAreaId();
            if ($areaId === null) {
                return;
            }

            $model = $builder->getModel();
            $column = $model->areaRestrictionColumn();
            $relation = $model->areaRestrictionRelation();

            if ($column) {
                $builder->where($model->getTable() . '.' . $column, $areaId);
            } elseif ($relation) {
                $builder->whereHas($relation);
            }
        });
    }

    /**
     * The area the current request is locked to, or null for no restriction.
     */
    protected static function restrictedAreaId(): ?int
    {
        $user = Auth::user();

        return $user && $user->area_id ? (int) $user->area_id : null;
    }

    public function areaRestrictionColumn(): ?string
    {
        return $this->areaColumn ?? null;
    }

    public function areaRestrictionRelation(): ?string
    {
        return $this->areaRelation ?? null;
    }
}
