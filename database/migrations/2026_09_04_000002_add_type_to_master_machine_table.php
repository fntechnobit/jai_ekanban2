<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_machine', function (Blueprint $table) {
            // A machine belongs to exactly one type (BONDER, DBL CRIMP, JOINT,
            // SHIELD, CUTTING, TWIST). Nullable so existing machines with no
            // matching usage below can be assigned manually later.
            $table->string('type', 20)->nullable()->after('machine');
        });

        // Backfill from actual usage - only exact machine-name matches, no
        // guessing by name prefix (family prefixes are not reliably 1:1 with
        // a type, e.g. "CM20.*" spans both JOINT and DBL CRIMP machines).
        $shikakeProcessByMachine = DB::table('master_shikake')
            ->whereNotNull('machine')
            ->where('machine', '!=', '')
            ->pluck('process', 'machine');

        $circuitMachines = DB::table('master_circuit')
            ->whereNotNull('machine')
            ->where('machine', '!=', '')
            ->distinct()
            ->pluck('machine');

        $twistMachines = DB::table('master_circuit')
            ->whereNotNull('machine_twist')
            ->where('machine_twist', '!=', '')
            ->distinct()
            ->pluck('machine_twist');

        foreach (DB::table('master_machine')->select('id', 'machine')->get() as $machine) {
            $type = null;

            if ($shikakeProcessByMachine->has($machine->machine)) {
                $type = $shikakeProcessByMachine->get($machine->machine);
            } elseif ($circuitMachines->contains($machine->machine)) {
                $type = 'CUTTING';
            } elseif ($twistMachines->contains($machine->machine)) {
                $type = 'TWIST';
            }

            if ($type !== null) {
                DB::table('master_machine')->where('id', $machine->id)->update(['type' => $type]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_machine', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
