<?php

namespace App\Services;

use App\Models\AdditionLogCircuit;
use App\Models\AdditionLogShikake;
use App\Models\DefectLogCircuit;
use App\Models\DefectLogShikake;
use App\Models\KanbanGenerationLog;
use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use App\Models\MasterShikake;
use Carbon\Carbon;

/**
 * Builds the daily balance-history report.
 *
 * For a given date D and item (circuit/shikake) it reconstructs how the balance
 * (`sisa`) moved that day using the recorded ground-truth snapshots from three
 * ledgers, all attributed to their business date:
 *
 *   - kanban_generation_log  → kebutuhan (listing demand consumed) + produced (kanban)
 *   - addition_log_*         → add cutting (+)
 *   - defect_log_*           → defect (−)
 *
 * Per item/day it reports:
 *   sisa_h1     = balance_before of the FIRST event of the day  (= end of yesterday)
 *   sisa_today  = balance_after  of the LAST  event of the day
 *   check       = sisa_today − (sisa_h1 + produced − kebutuhan + add − defect)  → 0 when consistent
 *
 * Only items that actually had activity on D are returned, so the report focuses
 * on rows whose balance changed and can be audited.
 */
class BalanceReportService
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, int>, date: string, prev_date: string}
     */
    public function buildRows(string $date, ?int $conveyorId = null, string $type = 'all'): array
    {
        $date     = Carbon::parse($date)->format('Y-m-d');
        $prevDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        $wantCircuit = ($type === 'all' || $type === 'circuit');
        $wantShikake = ($type === 'all' || $type === 'shikake');

        // key => ['meta'=>..., 'events'=>[...]]
        $items = [];

        $addEvent = function (string $key, array $meta, array $event) use (&$items) {
            if (!isset($items[$key])) {
                $items[$key] = ['meta' => $meta, 'events' => []];
            }
            $items[$key]['events'][] = $event;
        };

        // ---- Generation ledger ----
        $genQuery = KanbanGenerationLog::whereDate('schedule_date', $date);
        if ($conveyorId) {
            $genQuery->where('conveyor_id', $conveyorId);
        }
        if (!$wantCircuit) {
            $genQuery->where('item_type', '!=', KanbanGenerationLog::TYPE_CIRCUIT);
        }
        if (!$wantShikake) {
            $genQuery->where('item_type', '!=', KanbanGenerationLog::TYPE_SHIKAKE);
        }
        foreach ($genQuery->get() as $g) {
            $key = $g->item_type . '|' . $g->conveyor_id . '|' . $g->master_id;
            $addEvent($key, [
                'item_type'   => $g->item_type,
                'conveyor_id' => (int) $g->conveyor_id,
                'master_id'   => (int) $g->master_id,
            ], [
                'source'         => 'generate',
                'created_at'     => $g->created_at,
                'priority'       => 0,
                'balance_before' => (int) $g->sisa_before,
                'balance_after'  => (int) $g->sisa_after,
                'kebutuhan'      => (int) $g->kebutuhan,
                'produced'       => (int) $g->produced,
                'add'            => 0,
                'defect'         => 0,
            ]);
        }

        // ---- Addition logs ----
        if ($wantCircuit) {
            $q = AdditionLogCircuit::whereDate('addition_date', $date);
            if ($conveyorId) $q->where('conveyor_id', $conveyorId);
            foreach ($q->get() as $a) {
                $key = 'circuit|' . $a->conveyor_id . '|' . $a->master_circuit_id;
                $addEvent($key, [
                    'item_type'   => 'circuit',
                    'conveyor_id' => (int) $a->conveyor_id,
                    'master_id'   => (int) $a->master_circuit_id,
                ], $this->mutationEvent($a->created_at, 1, $a->balance_before, $a->balance_after, 'add', (int) $a->qty_addition));
            }
        }
        if ($wantShikake) {
            $q = AdditionLogShikake::whereDate('addition_date', $date);
            if ($conveyorId) $q->where('conveyor_id', $conveyorId);
            foreach ($q->get() as $a) {
                $key = 'shikake|' . $a->conveyor_id . '|' . $a->master_shikake_id;
                $addEvent($key, [
                    'item_type'   => 'shikake',
                    'conveyor_id' => (int) $a->conveyor_id,
                    'master_id'   => (int) $a->master_shikake_id,
                ], $this->mutationEvent($a->created_at, 1, $a->balance_before, $a->balance_after, 'add', (int) $a->qty_addition));
            }
        }

        // ---- Defect logs ----
        if ($wantCircuit) {
            $q = DefectLogCircuit::whereDate('defect_date', $date);
            if ($conveyorId) $q->where('conveyor_id', $conveyorId);
            foreach ($q->get() as $d) {
                $key = 'circuit|' . $d->conveyor_id . '|' . $d->master_circuit_id;
                $addEvent($key, [
                    'item_type'   => 'circuit',
                    'conveyor_id' => (int) $d->conveyor_id,
                    'master_id'   => (int) $d->master_circuit_id,
                ], $this->mutationEvent($d->created_at, 2, $d->balance_before, $d->balance_after, 'defect', (int) $d->qty_defect));
            }
        }
        if ($wantShikake) {
            $q = DefectLogShikake::whereDate('defect_date', $date);
            if ($conveyorId) $q->where('conveyor_id', $conveyorId);
            foreach ($q->get() as $d) {
                $key = 'shikake|' . $d->conveyor_id . '|' . $d->master_shikake_id;
                $addEvent($key, [
                    'item_type'   => 'shikake',
                    'conveyor_id' => (int) $d->conveyor_id,
                    'master_id'   => (int) $d->master_shikake_id,
                ], $this->mutationEvent($d->created_at, 2, $d->balance_before, $d->balance_after, 'defect', (int) $d->qty_defect));
            }
        }

        if (empty($items)) {
            return ['rows' => [], 'totals' => $this->emptyTotals(), 'date' => $date, 'prev_date' => $prevDate];
        }

        // ---- Resolve item metadata (codes / conveyor names) ----
        $meta = $this->resolveMeta($items);

        // ---- Aggregate per item ----
        $rows = [];
        foreach ($items as $key => $item) {
            // Chronological order so balance_before/after chain correctly.
            usort($item['events'], function ($a, $b) {
                $ta = $a['created_at'] ? $a['created_at']->timestamp : 0;
                $tb = $b['created_at'] ? $b['created_at']->timestamp : 0;
                return [$ta, $a['priority']] <=> [$tb, $b['priority']];
            });

            $first = $item['events'][0];
            $last  = $item['events'][count($item['events']) - 1];

            $kebutuhan = array_sum(array_column($item['events'], 'kebutuhan'));
            $produced  = array_sum(array_column($item['events'], 'produced'));
            $add       = array_sum(array_column($item['events'], 'add'));
            $defect    = array_sum(array_column($item['events'], 'defect'));

            $sisaH1    = (int) $first['balance_before'];
            $sisaToday = (int) $last['balance_after'];
            $check     = $sisaToday - ($sisaH1 + $produced - $kebutuhan + $add - $defect);

            $m = $meta[$key] ?? [];
            $rows[] = [
                'item_type'     => $item['meta']['item_type'],
                'conveyor_id'   => $item['meta']['conveyor_id'],
                'conveyor_name' => $m['conveyor_name'] ?? ('CV#' . $item['meta']['conveyor_id']),
                'type_label'    => $m['type_label'] ?? '-',
                'code'          => $m['code'] ?? ('#' . $item['meta']['master_id']),
                'sisa_h1'       => $sisaH1,
                'kebutuhan'     => (int) $kebutuhan,
                'produced'      => (int) $produced,
                'add'           => (int) $add,
                'defect'        => (int) $defect,
                'sisa_today'    => $sisaToday,
                'check'         => (int) $check,
            ];
        }

        // Sort by conveyor, then type, then code
        usort($rows, function ($a, $b) {
            return [$a['conveyor_name'], $a['item_type'], $a['code']]
               <=> [$b['conveyor_name'], $b['item_type'], $b['code']];
        });

        $totals = [
            'sisa_h1'    => array_sum(array_column($rows, 'sisa_h1')),
            'kebutuhan'  => array_sum(array_column($rows, 'kebutuhan')),
            'produced'   => array_sum(array_column($rows, 'produced')),
            'add'        => array_sum(array_column($rows, 'add')),
            'defect'     => array_sum(array_column($rows, 'defect')),
            'sisa_today' => array_sum(array_column($rows, 'sisa_today')),
            'check'      => array_sum(array_column($rows, 'check')),
        ];

        return ['rows' => $rows, 'totals' => $totals, 'date' => $date, 'prev_date' => $prevDate];
    }

    private function mutationEvent($createdAt, int $priority, $before, $after, string $kind, int $qty): array
    {
        return [
            'source'         => $kind,
            'created_at'     => $createdAt,
            'priority'       => $priority,
            'balance_before' => (int) $before,
            'balance_after'  => (int) $after,
            'kebutuhan'      => 0,
            'produced'       => 0,
            'add'            => $kind === 'add' ? $qty : 0,
            'defect'         => $kind === 'defect' ? $qty : 0,
        ];
    }

    /**
     * Resolve conveyor name + item code/type label for each item key.
     */
    private function resolveMeta(array $items): array
    {
        $circuitIds = [];
        $shikakeIds = [];
        $conveyorIds = [];

        foreach ($items as $item) {
            $conveyorIds[$item['meta']['conveyor_id']] = true;
            if ($item['meta']['item_type'] === 'circuit') {
                $circuitIds[$item['meta']['master_id']] = true;
            } else {
                $shikakeIds[$item['meta']['master_id']] = true;
            }
        }

        $conveyors = MasterConveyor::whereIn('id', array_keys($conveyorIds))->pluck('conveyor', 'id');
        $circuits  = MasterCircuit::whereIn('id', array_keys($circuitIds))->get()->keyBy('id');
        $shikakes  = MasterShikake::whereIn('id', array_keys($shikakeIds))->get()->keyBy('id');

        $meta = [];
        foreach ($items as $key => $item) {
            $cid = $item['meta']['conveyor_id'];
            $mid = $item['meta']['master_id'];

            if ($item['meta']['item_type'] === 'circuit') {
                $c = $circuits->get($mid);
                $meta[$key] = [
                    'conveyor_name' => $conveyors[$cid] ?? ('CV#' . $cid),
                    'type_label'    => $c ? (($c->type === 'CUTTING_TWIST') ? 'TWS' : 'CCT') : 'CCT',
                    'code'          => $c ? trim(($c->cct_no ?? '') . ' - ' . ($c->cct_code ?? '')) : ('#' . $mid),
                ];
            } else {
                $s = $shikakes->get($mid);
                $meta[$key] = [
                    'conveyor_name' => $conveyors[$cid] ?? ('CV#' . $cid),
                    'type_label'    => $s ? strtoupper($s->process ?? 'SHIKAKE') : 'SHIKAKE',
                    'code'          => $s ? ($s->machine ?? ('SHK-' . $mid)) : ('#' . $mid),
                ];
            }
        }

        return $meta;
    }

    private function emptyTotals(): array
    {
        return [
            'sisa_h1' => 0, 'kebutuhan' => 0, 'produced' => 0,
            'add' => 0, 'defect' => 0, 'sisa_today' => 0, 'check' => 0,
        ];
    }
}
