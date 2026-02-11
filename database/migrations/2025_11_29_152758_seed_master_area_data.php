<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $areas = [
            'BATTERY CABLE',
            'BIG SIZE',
            'GT43',
            'MAZDA',
            'MEZZANINE',
            'NISSAN',
            'TNGA',
            'TOYOTA GEDUNG A',
            'TOYOTA GEDUNG C',
            'TWIST GENBA A',
        ];

        foreach ($areas as $area) {
            DB::table('master_area')->insert([
                'area' => $area,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /// Irreversible
    }
};
