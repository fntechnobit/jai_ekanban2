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
        // Rentang hanya dipakai bila hasilnya PASTI sama dengan whereDate(): nilai
        // 'Y-m-d' atau 'Y-m-d 00:00:00' yang valid. Selain itu (kosong, format lain,
        // jam bukan 00:00) tetap whereDate() supaya perilakunya tidak berubah.
        $value = is_string($date) ? trim($date) : (string) $date;
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: 00:00:00)?$/', $value, $m)
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $query->whereDate($column, $date);
        }

        $day = Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}")->startOfDay();

        return $query->where($column, '>=', $day->toDateTimeString())
            ->where($column, '<', $day->copy()->addDay()->toDateTimeString());
    }
}
