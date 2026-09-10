<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the schedule generation path.
     *
     * generateSchedules() filters assy_schedule by (schedule, assycode, assy) in a
     * correlated NOT EXISTS - once per candidate listing row - and by
     * (schedule, conveyor_id) again for every conveyor/date in the loop
     * (lock check + cleanup). None of those columns was indexed, so each check
     * fell back to scanning assy_schedule through the conveyor_id index and
     * filtering the rest by hand. Locally (3.6k schedules) that is invisible;
     * on the server the same query walks two orders of magnitude more rows.
     *
     * master_conveyor.conveyor is matched by name inside that same subquery and
     * once per listing group, and was a full table scan every time.
     */
    public function up(): void
    {
        $this->addIndex('assy_schedule', 'idx_assy_schedule_date_conveyor', ['schedule', 'conveyor_id']);
        $this->addIndex('assy_schedule', 'idx_assy_schedule_date_assy', ['schedule', 'assycode', 'assy']);
        $this->addIndex('master_conveyor', 'idx_master_conveyor_name', ['conveyor']);
    }

    public function down(): void
    {
        $this->dropIndex('assy_schedule', 'idx_assy_schedule_date_conveyor');
        $this->dropIndex('assy_schedule', 'idx_assy_schedule_date_assy');
        $this->dropIndex('master_conveyor', 'idx_master_conveyor_name');
    }

    /**
     * Create the index only when it is missing, so re-running on a database that
     * already has it (manually added on the server) is not an error.
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if ($this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name, $columns) {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!$this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropIndex($name);
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn ($index) => $index->Key_name === $name);
    }
};
