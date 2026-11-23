<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Filter "kolom datetime jatuh pada tanggal X" yang tetap bisa memakai index.
 *
 * whereDate('kolom', X) menghasilkan DATE(kolom) = X: MySQL harus menghitung
 * DATE() untuk setiap baris sehingga index pada kolom itu tidak terpakai.
 * Rentang [X 00:00:00, X+1 00:00:00) memberi hasil yang sama dan memakai index
 * (mis. idx_assy_schedule_date_conveyor pada assy_schedule.schedule).
 */
class SameDay
{
    public static function where($query, string $column, $date)
    {
        $day = Carbon::parse($date)->startOfDay();

        return $query->where($column, '>=', $day->toDateTimeString())
            ->where($column, '<', $day->copy()->addDay()->toDateTimeString());
    }
}
