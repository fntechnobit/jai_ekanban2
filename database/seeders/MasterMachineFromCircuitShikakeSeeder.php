<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterMachineFromCircuitShikakeSeeder extends Seeder
{
    /**
     * Ambil semua distinct machine dari master_circuit dan master_shikake,
     * insert ke master_machine (skip duplikat), lalu link ke conveyor B3-EGI dan B3-ENG.
     */
    public function run(): void
    {
        // Ambil conveyor ID untuk B3-EGI dan B3-ENG
        $conveyors = DB::table('master_conveyor')
            ->whereIn('conveyor', ['B3-EGI', 'B3-ENG'])
            ->whereNull('deleted_at')
            ->pluck('id', 'conveyor');

        if ($conveyors->isEmpty()) {
            $this->command->warn('Conveyor B3-EGI dan/atau B3-ENG tidak ditemukan. Seeder dihentikan.');
            return;
        }

        $this->command->info('Ditemukan conveyor: ' . $conveyors->keys()->implode(', '));

        // Kumpulkan semua nilai machine dari kedua tabel, buang yang null/kosong
        $machinesCircuit = DB::table('master_circuit')
            ->whereNotNull('machine')
            ->where('machine', '!=', '')
            ->distinct()
            ->pluck('machine');

        $machinesShikake = DB::table('master_shikake')
            ->whereNotNull('machine')
            ->where('machine', '!=', '')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('machine');

        $allMachines = $machinesCircuit
            ->merge($machinesShikake)
            ->map(fn($m) => trim($m))
            ->filter(fn($m) => $m !== '')
            ->unique()
            ->sort()
            ->values();

        $this->command->info("Total distinct machine ditemukan: {$allMachines->count()}");

        $inserted = 0;
        $skipped  = 0;

        foreach ($allMachines as $machineName) {
            // Cek apakah sudah ada di master_machine (termasuk soft-deleted)
            $existing = DB::table('master_machine')
                ->where('machine', $machineName)
                ->first();

            if ($existing) {
                $machineId = $existing->id;

                // Jika soft-deleted, restore
                if (!empty($existing->deleted_at)) {
                    DB::table('master_machine')
                        ->where('id', $machineId)
                        ->update(['deleted_at' => null, 'deleted_by' => null]);
                }

                $skipped++;
            } else {
                $machineId = DB::table('master_machine')->insertGetId([
                    'machine'    => $machineName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }

            // Link ke conveyor B3-EGI dan B3-ENG via pivot (skip jika sudah ada)
            foreach ($conveyors as $conveyorName => $conveyorId) {
                $pivotExists = DB::table('master_machine_conveyor')
                    ->where('machine_id', $machineId)
                    ->where('conveyor_id', $conveyorId)
                    ->exists();

                if (!$pivotExists) {
                    DB::table('master_machine_conveyor')->insert([
                        'machine_id'  => $machineId,
                        'conveyor_id' => $conveyorId,
                    ]);
                }
            }
        }

        $this->command->info("Selesai: {$inserted} machine baru diinsert, {$skipped} sudah ada (skip).");
        $this->command->info("Semua machine sudah dilink ke: " . $conveyors->keys()->implode(', '));
    }
}
