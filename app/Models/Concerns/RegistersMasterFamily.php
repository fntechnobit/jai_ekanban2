<?php

namespace App\Models\Concerns;

use App\Models\MasterFamily;

/**
 * For models that carry a free-text `family` and a `conveyor_id` (Circuit,
 * Shikake): whenever a row is saved - from a form or an Excel import - its
 * family is registered in master family under the conveyor's area, so the
 * master list always includes every family that has been entered.
 */
trait RegistersMasterFamily
{
    public static function bootRegistersMasterFamily(): void
    {
        static::saved(function ($model) {
            if ($model->wasRecentlyCreated || $model->wasChanged(['family', 'conveyor_id'])) {
                MasterFamily::ensureForConveyor($model->family, $model->conveyor_id);
            }
        });
    }
}
