<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Merge duplicate user groups (same name) into a single keeper row, then
     * enforce a unique constraint on the name so duplicates cannot recur.
     */
    public function up(): void
    {
        $duplicateNames = DB::table('user_groups')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            $rows = DB::table('user_groups')->where('name', $name)->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'users' => DB::table('users')->where('group_id', $row->id)->count(),
                        'menus' => DB::table('group_menu_access')->where('group_id', $row->id)->count(),
                    ];
                })
                ->all();

            // Keeper = most used (users, then menu access), tie-break on lowest id.
            usort($rows, function ($a, $b) {
                return [$b['users'], $b['menus'], $a['id']] <=> [$a['users'], $a['menus'], $b['id']];
            });

            $keeperId = $rows[0]['id'];
            $duplicateIds = array_column(array_slice($rows, 1), 'id');

            if (empty($duplicateIds)) {
                continue;
            }

            // Move any users off the duplicates onto the keeper so none is orphaned.
            DB::table('users')->whereIn('group_id', $duplicateIds)->update(['group_id' => $keeperId]);

            // Delete duplicates (their group_menu_access rows cascade away).
            DB::table('user_groups')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('user_groups', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropUnique('user_groups_name_unique');
        });
    }
};
