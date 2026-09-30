<?php

namespace App\Services;

use App\Enums\MachineType;
use App\Config\MachineTemplateConfig;
use App\Models\MasterArea;
use App\Models\MasterMachine;
use App\Models\MasterMachineConveyor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class MasterMachineService
{
    public function getAll()
    {
        return MasterMachine::with(['area', 'conveyors'])->select('master_machine.*');
    }

    public function getDatatable($areaId = null, $conveyorId = null)
    {
        $query = MasterMachine::with(['area', 'conveyors', 'conveyors.area'])
            ->select('master_machine.*');

        // Filter by area
        if ($areaId) {
            $query->where('master_machine.master_area_id', $areaId);
        }

        // Filter by conveyor
        if ($conveyorId) {
            $query->whereHas('conveyors', function ($q) use ($conveyorId) {
                $q->where('master_conveyor.id', $conveyorId);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type_badge', function ($row) {
                if (!$row->type) {
                    return '-';
                }
                $badgeClass = match ($row->type) {
                    'BONDER' => 'bg-success',
                    'JOINT' => 'bg-info',
                    'SHIELD' => 'bg-warning',
                    'DBL CRIMP' => 'bg-secondary',
                    'CUTTING' => 'bg-primary',
                    'TWIST' => 'bg-dark',
                    default => 'bg-dark',
                };
                return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row->type, ENT_QUOTES) . '</span>';
            })
            ->addColumn('area_name', function ($row) {
                return $row->area->area ?? '-';
            })
            ->addColumn('conveyor_names', function ($row) {
                return $row->conveyors->pluck('conveyor')->implode(', ') ?: '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                if ($currentUser && $currentUser->hasMenuPermission('master_machine', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_machine', 'can_delete')) {
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="' . $row->id . '" title="Delete"><i class="ti ti-trash"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
            })
            ->rawColumns(['action', 'type_badge'])
            ->make(true);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = Auth::id();
            
            $machine = MasterMachine::create($data);

            // Attach conveyors
            if (!empty($data['conveyor_ids'])) {
                foreach ($data['conveyor_ids'] as $conveyorId) {
                    MasterMachineConveyor::create([
                        'machine_id' => $machine->id,
                        'conveyor_id' => $conveyorId,
                    ]);
                }
            }

            DB::commit();
            return $machine->load(['area', 'conveyors']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(MasterMachine $machine, array $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $machine->update($data);

            // Sync conveyors
            if (isset($data['conveyor_ids'])) {
                // Remove existing
                MasterMachineConveyor::where('machine_id', $machine->id)->delete();
                
                // Add new
                foreach ($data['conveyor_ids'] as $conveyorId) {
                    MasterMachineConveyor::create([
                        'machine_id' => $machine->id,
                        'conveyor_id' => $conveyorId,
                    ]);
                }
            }

            DB::commit();
            return $machine->load(['area', 'conveyors']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(MasterMachine $machine)
    {
        $machine->deleted_by = Auth::id();
        $machine->save();
        $machine->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterMachine::with(['area', 'conveyors'])->findOrFail($id);
    }

    public function import($filePath, $areaId, $startRow = 2)
    {
        $importer = new \App\Imports\MasterMachineImport($areaId);
        return $importer->import($filePath, $startRow);
    }

    /**
     * Build the Machine import template as a temp .xlsx file, scoped to one
     * area, and return its path. Area is not a column here - it is chosen
     * once for the whole import batch (like Circuit's conveyor_id) - so the
     * template only needs a Type dropdown, sourced from a hidden "Lists" sheet.
     */
    public function generateTemplateFile(int $areaId): string
    {
        $area = MasterArea::findOrFail($areaId);
        $types = MachineType::toArray();

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        // Sheet name carries the area so it stays visible even if the file
        // gets renamed; row 1 stays the header row (data import reads it).
        $safeAreaName = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $area->area);
        $sheet->setTitle(mb_substr('Machine - ' . $safeAreaName, 0, 31));

        $headers = MachineTemplateConfig::getHeaders();
        $columnLetters = ['A', 'B'];
        foreach ($headers as $i => $header) {
            $cell = $sheet->getCell($columnLetters[$i] . '1');
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DDEBF7');
        }
        foreach (['A' => 30, 'B' => 15] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Hidden sheet holding the Type dropdown source list
        $listSheet = new Worksheet($spreadsheet, 'Lists');
        $spreadsheet->addSheet($listSheet);
        $listSheet->setCellValue('A1', 'Type');
        foreach ($types as $i => $type) {
            $listSheet->setCellValue('A' . ($i + 2), $type);
        }
        $listSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

        $firstDataRow = 2;
        $lastDataRow = 1000;

        $typeValidation = new DataValidation();
        $typeValidation->setType(DataValidation::TYPE_LIST);
        $typeValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $typeValidation->setAllowBlank(true);
        $typeValidation->setShowDropDown(true);
        $typeValidation->setShowErrorMessage(true);
        $typeValidation->setErrorTitle('Invalid Type');
        $typeValidation->setError('Please choose a Type from the dropdown list.');
        $typeValidation->setFormula1('Lists!$A$2:$A$' . (count($types) + 1));
        $sheet->setDataValidation('B' . $firstDataRow . ':B' . $lastDataRow, clone $typeValidation);

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = tempnam(sys_get_temp_dir(), 'machine_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
