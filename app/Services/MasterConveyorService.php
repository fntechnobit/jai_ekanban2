<?php

namespace App\Services;

use App\Models\MasterConveyor;
use App\Models\MasterFamilyConveyor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterConveyorService
{
    public function getAll()
    {
        return MasterConveyor::with(['area', 'families'])->select('master_conveyor.*');
    }

    public function getDatatable($areaId = null, $familyId = null, $status = null)
    {
        $query = MasterConveyor::with(['area', 'families'])
            ->select('master_conveyor.*');

        // Filter by area
        if ($areaId) {
            $query->where('master_area_id', $areaId);
        }

        // Filter by status aktif/nonaktif
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Filter by family
        if ($familyId) {
            $query->whereHas('families', function ($q) use ($familyId) {
                $q->where('master_family.id', $familyId);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('area_name', function ($row) {
                return $row->area ? $row->area->area : '-';
            })
            ->addColumn('family_names', function ($row) {
                return $row->families->pluck('family')->implode(', ') ?: '-';
            })
            ->addColumn('status_label', function ($row) {
                // Status berasal dari SIREP: nonaktif = tidak muncul lagi di API.
                if ($row->is_active) {
                    return '<span class="badge bg-success">Aktif</span>';
                }

                $sejak = $row->deactivated_at ? ' sejak ' . $row->deactivated_at->format('d M Y') : '';

                return '<span class="badge bg-secondary" title="Tidak ada lagi di SIREP' . e($sejak)
                    . '. Tidak ikut dijadwalkan maupun diverifikasi.">Nonaktif</span>';
            })
            ->addColumn('ot_label', function ($row) {
                // Ambang pemecahan shift. Bila SIREP belum mengirimnya, dipakai
                // normal_capacity — conveyor tetap terjadwal tetapi tanpa jatah lembur.
                if (!$row->overtime_capacity) {
                    if (!$row->capacity) {
                        return '<span class="badge bg-danger" title="Kapasitas SIREP belum ada — conveyor ini dilewati saat generate">belum ada</span>';
                    }

                    return '<span class="text-muted">' . number_format((int) $row->capacity) . '</span>'
                        . '<br><small class="text-warning-emphasis" '
                        . 'title="SIREP belum mengirim overtime_capacity. Sementara dipakai kapasitas normal, '
                        . 'artinya conveyor ini dianggap tanpa jatah lembur.">pakai kapasitas normal</small>';
                }

                $selisih = (int) $row->overtime_capacity - (int) ($row->capacity ?? 0);

                return '<span class="fw-semibold">' . number_format((int) $row->overtime_capacity) . '</span>'
                    . '<br><small class="text-muted">CO5 = ' . number_format(max(0, $selisih)) . '</small>';
            })
            ->addColumn('capacity_label', function ($row) {
                // Kapasitas milik SIREP: tampilkan apa adanya, termasuk saat belum pernah
                // disinkron — kondisi itu yang membuat conveyor dilewati saat generate.
                if (!$row->hasSyncedCapacity()) {
                    return '<span class="badge bg-danger">belum sinkron</span>';
                }

                $over = $row->overtime_capacity ? ' <span class="text-muted">/ ' . (int) $row->overtime_capacity . ' OT</span>' : '';

                return '<span class="fw-semibold">' . (int) $row->capacity . '</span>' . $over;
            })
            ->addColumn('synced_label', function ($row) {
                return $row->capacity_synced_at
                    ? $row->capacity_synced_at->format('d M Y H:i')
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                if ($currentUser && $currentUser->hasMenuPermission('master_conveyor', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
            })
            ->rawColumns(['status_label', 'ot_label', 'capacity_label', 'synced_label', 'action'])
            ->make(true);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = Auth::id();

            $conveyor = MasterConveyor::create($data);

            // Attach families
            if (!empty($data['family_ids'])) {
                foreach ($data['family_ids'] as $familyId) {
                    MasterFamilyConveyor::create([
                        'conveyor_id' => $conveyor->id,
                        'family_id' => $familyId,
                    ]);
                }
            }

            DB::commit();
            return $conveyor->load('families');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(MasterConveyor $conveyor, array $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $conveyor->update($data);

            // Sync families
            if (isset($data['family_ids'])) {
                // Remove existing
                MasterFamilyConveyor::where('conveyor_id', $conveyor->id)->delete();
                
                // Add new
                foreach ($data['family_ids'] as $familyId) {
                    MasterFamilyConveyor::create([
                        'conveyor_id' => $conveyor->id,
                        'family_id' => $familyId,
                    ]);
                }
            }

            DB::commit();
            return $conveyor->load('families');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(MasterConveyor $conveyor)
    {
        $conveyor->deleted_by = Auth::id();
        $conveyor->save();
        $conveyor->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterConveyor::with('families')->findOrFail($id);
    }
}
