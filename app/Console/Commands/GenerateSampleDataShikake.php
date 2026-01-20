<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Config\ShikakeTemplateConfig;
use App\Models\MasterAssy;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GenerateSampleDataShikake extends Command
{
    protected $signature = 'sample:generate-shikake {--count=10 : Number of unique identifiers to generate}';
    protected $description = 'Generate sample data Excel files for Master Shikake and Master Circuit imports';

    protected $assyNames = [];
    protected $machineList = ['CM30.NSN-02', 'AC95.NSN-01', 'CAST.NSN-01'];

    public function handle()
    {
        $count = (int) $this->option('count');
        $count = max(5, min(20, $count)); // Clamp between 5-20 identifiers

        $this->info("Generating sample data with {$count} unique identifiers each...");

        // Use predefined assy codes for sample data
        $this->assyNames = [
            '82111-1KS10',
            '24012-6YM0B',
            '82115-F2X50',
            'KJM9-67030',
            '82111-F2L51',
            '82111-F2W82',
            '82111-F2W62',
            '82111-F2210',
            '82111-F2220',
            '24012-6YM1B',
        ];
        
        // Limit to first 5 assy for sample columns
        $this->assyNames = array_slice($this->assyNames, 0, 5);
        $this->info('Using ' . count($this->assyNames) . ' sample assy codes.');

        // Create samples directory in storage if not exists
        $samplesPath = storage_path('app/samples');
        if (!File::isDirectory($samplesPath)) {
            File::makeDirectory($samplesPath, 0755, true);
        }

        // Add .gitignore to samples folder
        $gitignorePath = $samplesPath . '/.gitignore';
        if (!File::exists($gitignorePath)) {
            File::put($gitignorePath, "*\n!.gitignore\n");
        }

        // Generate Shikake sample files
        $this->generateShikakeSample($samplesPath, 'TWIST', $count);
        $this->generateShikakeSample($samplesPath, 'BONDER', $count);
        $this->generateShikakeSample($samplesPath, 'JOINT', $count);
        $this->generateShikakeSample($samplesPath, 'SHIELD', $count);
        $this->generateShikakeSample($samplesPath, 'DBL CRIMP', $count);

        // Generate Circuit sample file
        $this->generateCircuitSample($samplesPath, $count);

        $this->info('All sample data files generated successfully!');
        $this->info("Files saved to: {$samplesPath}");
        return Command::SUCCESS;
    }

    /**
     * Generate issue format like 001/004
     */
    protected function generateIssueFormat(int $current, int $total): string
    {
        return str_pad($current, 3, '0', STR_PAD_LEFT) . '/' . str_pad($total, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate random assy assignments for a group
     */
    protected function generateAssyAssignments(): array
    {
        $assyCount = rand(1, min(2, count($this->assyNames)));
        $assignedAssyKeys = (array) array_rand(array_flip($this->assyNames), $assyCount);
        return array_intersect($this->assyNames, $assignedAssyKeys);
    }

    protected function generateShikakeSample($samplesPath, $processType, $identifierCount)
    {
        $headers = $this->getShikakeHeaders($processType);
        $filename = 'Sample_Shikake_' . str_replace(' ', '_', $processType) . '.xlsx';
        $filePath = $samplesPath . '/' . $filename;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Set required headers
        $col = 1;
        foreach ($headers as $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $header);
            $col++;
        }

        // Add assy columns
        $assyStartCol = $col;
        foreach ($this->assyNames as $assyName) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $assyName);
            $col++;
        }

        // Style required header columns (blue background)
        $lastRequiredColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $this->styleHeaderRange($sheet, "A1:{$lastRequiredColumn}1", '4472C4', 'FFFFFF');

        // Style assy columns (light green background)
        if (!empty($this->assyNames)) {
            $firstAssyColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol);
            $lastAssyColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol + count($this->assyNames) - 1);
            $this->styleHeaderRange($sheet, "{$firstAssyColumn}1:{$lastAssyColumn}1", '92D050', '000000', true);
        }

        // Generate sample data rows with identifier grouping
        $row = 2;
        $globalRowIndex = 0;

        for ($identifierIndex = 1; $identifierIndex <= $identifierCount; $identifierIndex++) {
            // Random number of issues per identifier (2-5)
            $issueCount = rand(2, 5);
            
            // Generate shared data for this identifier group
            $sharedData = $this->generateSharedShikakeData($processType, $identifierIndex);
            $assyAssignments = $this->generateAssyAssignments();

            for ($issueNum = 1; $issueNum <= $issueCount; $issueNum++) {
                $globalRowIndex++;
                
                // Generate row data with shared identifier but unique barcodes/issue
                $data = $this->generateShikakeRowData(
                    $processType,
                    $sharedData,
                    $issueNum,
                    $issueCount,
                    $globalRowIndex
                );
                
                $col = 1;
                foreach ($data as $value) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $value);
                    $col++;
                }

                // Add assy values (same for all rows in identifier group)
                foreach ($this->assyNames as $index => $assyName) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol + $index);
                    $value = in_array($assyName, $assyAssignments) ? '1' : '';
                    $sheet->setCellValue($colLetter . $row, $value);
                }

                $row++;
            }
        }

        // Auto-size columns
        $totalColumns = count($headers) + count($this->assyNames);
        foreach (range(1, $totalColumns) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Write the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $totalRows = $row - 2;
        $this->info("Created: {$filename} ({$identifierCount} identifiers, {$totalRows} rows)");
    }

    protected function generateCircuitSample($samplesPath, $identifierCount)
    {
        $headers = $this->getCircuitHeaders();
        $filename = 'Sample_Cutting.xlsx';
        $filePath = $samplesPath . '/' . $filename;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Set required headers
        $col = 1;
        foreach ($headers as $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $header);
            $col++;
        }

        // Add assy columns
        $assyStartCol = $col;
        foreach ($this->assyNames as $assyName) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $assyName);
            $col++;
        }

        // Style required header columns (blue background)
        $lastRequiredColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $this->styleHeaderRange($sheet, "A1:{$lastRequiredColumn}1", '4472C4', 'FFFFFF');

        // Style assy columns (light green background)
        if (!empty($this->assyNames)) {
            $firstAssyColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol);
            $lastAssyColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol + count($this->assyNames) - 1);
            $this->styleHeaderRange($sheet, "{$firstAssyColumn}1:{$lastAssyColumn}1", '92D050', '000000', true);
        }

        // Generate sample data rows with identifier grouping
        $row = 2;
        $globalRowIndex = 0;

        for ($identifierIndex = 1; $identifierIndex <= $identifierCount; $identifierIndex++) {
            // Random number of issues per identifier (2-5)
            $issueCount = rand(2, 5);
            
            // Generate shared data for this identifier group
            $sharedData = $this->generateSharedCircuitData($identifierIndex);
            $assyAssignments = $this->generateAssyAssignments();

            for ($issueNum = 1; $issueNum <= $issueCount; $issueNum++) {
                $globalRowIndex++;
                
                // Generate row data with shared identifier but unique barcodes/issue
                $data = $this->generateCircuitRowData(
                    $sharedData,
                    $issueNum,
                    $issueCount,
                    $globalRowIndex
                );
                
                $col = 1;
                foreach ($data as $value) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colLetter . $row, $value);
                    $col++;
                }

                // Add assy values (same for all rows in identifier group)
                foreach ($this->assyNames as $index => $assyName) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol + $index);
                    $value = in_array($assyName, $assyAssignments) ? '1' : '';
                    $sheet->setCellValue($colLetter . $row, $value);
                }

                $row++;
            }
        }

        // Auto-size columns
        $totalColumns = count($headers) + count($this->assyNames);
        foreach (range(1, $totalColumns) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Write the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $totalRows = $row - 2;
        $this->info("Created: {$filename} ({$identifierCount} identifiers, {$totalRows} rows)");
    }

    protected function styleHeaderRange($sheet, $range, $bgColor, $fontColor, $italic = false)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'italic' => $italic,
                'color' => ['rgb' => $fontColor],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgColor],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function getShikakeHeaders($processType)
    {
        return match ($processType) {
            'TWIST' => ShikakeTemplateConfig::getTwistHeaders(),
            'BONDER' => ShikakeTemplateConfig::getBonderHeaders(),
            'JOINT' => ShikakeTemplateConfig::getJointHeaders(),
            'SHIELD' => ShikakeTemplateConfig::getShieldHeaders(),
            'DBL CRIMP' => ShikakeTemplateConfig::getDblCrimpHeaders(),
            default => ShikakeTemplateConfig::getBaseHeaders(),
        };
    }

    protected function getCircuitHeaders()
    {
        return [
            'Conveyor',       // 0
            'CCT No.',        // 1
            'Family',         // 2
            'Qty.',           // 3
            'Issue',          // 4
            'Machine',        // 5
            'Sequence',       // 6
            'Barcode Kanban', // 7
            'Released Date',  // 8
            'Released Note',  // 9
            'Cust No.',       // 10
            'Barcode Mesin',  // 11
            'Address',        // 12
            'CCT Code',       // 13
            'Kind',           // 14
            'Size',           // 15
            'Col',            // 16
            'C/L',            // 17
            'Terminal 1',     // 18
            'Note 1',         // 19
            'Gold 1',         // 20
            'Strip 1',        // 21
            'Acc. 1',         // 22
            'Acc. 1A',        // 23
            'Tube 1',         // 24
            'Mark 1',         // 25
            'Remark 1',       // 26
            'Terminal 2',     // 27
            'Note 2',         // 28
            'Gold 2',         // 29
            'Strip 2',        // 30
            'Acc 2',          // 31
            'Acc 2A',         // 32
            'Tube 2',         // 33
            'Mark 2',         // 34
            'Remark 2',       // 35
            'TA',             // 36
            'TB',             // 37
            'T01',            // 38
            'T02',            // 39
            'T03',            // 40
            'T04',            // 41
            'T05',            // 42
            'T06',            // 43
        ];
    }

    /**
     * Generate shared data for a Shikake identifier group
     */
    protected function generateSharedShikakeData($processType, $identifierIndex)
    {
        $shared = [
            'conveyor' => 'CONV-' . str_pad($identifierIndex, 3, '0', STR_PAD_LEFT),
            'machine' => $this->machineList[($identifierIndex - 1) % count($this->machineList)],
            'qty' => rand(50, 500),
            'family' => 'FAM-' . chr(65 + ($identifierIndex % 5)),
            'released_date' => date('Y-m-d', strtotime('+' . rand(1, 30) . ' days')),
            'released_note' => 'Note for group ' . $identifierIndex,
        ];

        // Add process-specific shared data (identifiers)
        switch ($processType) {
            case 'TWIST':
                $shared['cct_code'] = 'CCT-' . str_pad($identifierIndex, 4, '0', STR_PAD_LEFT);
                $shared['cct_no'] = 'CCT-NO-' . str_pad($identifierIndex, 3, '0', STR_PAD_LEFT);
                $shared['machine_twist'] = 'MT-' . str_pad(rand(1, 5), 2, '0', STR_PAD_LEFT);
                $shared['to_store'] = 'STORE-' . chr(65 + ($identifierIndex % 3));
                $shared['cust_no'] = 'CUST-' . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT);
                $shared['kind'] = 'TYPE-' . chr(65 + ($identifierIndex % 4));
                $shared['size'] = rand(1, 10) . '.0';
                $shared['color'] = ['RED', 'BLUE', 'GREEN', 'BLACK', 'WHITE'][$identifierIndex % 5];
                $shared['cl'] = rand(100, 500);
                $shared['terminal_a'] = 'TERM-A-' . $identifierIndex;
                $shared['acc_1_a'] = 'ACC-A-' . $identifierIndex;
                $shared['tube_a'] = 'TUBE-A';
                $shared['note_a'] = 'Note A';
                $shared['strip_a'] = rand(5, 15) . 'mm';
                $shared['mark_a'] = 'MK-A';
                $shared['terminal_b'] = 'TERM-B-' . $identifierIndex;
                $shared['acc_1_ab'] = 'ACC-B-' . $identifierIndex;
                $shared['tube_b'] = 'TUBE-B';
                $shared['note_b'] = 'Note B';
                $shared['strip_b'] = rand(5, 15) . 'mm';
                $shared['mark_b'] = 'MK-B';
                break;

            case 'BONDER':
                $shared['bonder_no'] = 'BND-' . str_pad($identifierIndex, 4, '0', STR_PAD_LEFT);
                $shared['address'] = 'ADDR-' . chr(65 + ($identifierIndex % 5));
                $shared['dies'] = 'DIE-' . str_pad(rand(1, 20), 2, '0', STR_PAD_LEFT);
                $shared['to_machine'] = 'TO-MACH-' . rand(1, 10);
                // CCT and Bonder pairs
                for ($i = 1; $i <= 7; $i++) {
                    $shared["cct_no_a_{$i}"] = 'CCT-A' . $i . '-' . $identifierIndex;
                    $shared["bonder_no_a_{$i}"] = 'BND-A' . $i . '-' . $identifierIndex;
                    $shared["cct_no_b_{$i}"] = 'CCT-B' . $i . '-' . $identifierIndex;
                    $shared["bonder_no_b_{$i}"] = 'BND-B' . $i . '-' . $identifierIndex;
                }
                break;

            case 'JOINT':
                $shared['bonder_no'] = 'JNT-' . str_pad($identifierIndex, 4, '0', STR_PAD_LEFT);
                $shared['address'] = 'ADDR-' . chr(65 + ($identifierIndex % 5));
                $shared['address_store'] = 'STORE-' . chr(65 + ($identifierIndex % 3));
                $shared['to_machine'] = 'TO-MACH-' . rand(1, 10);
                for ($i = 1; $i <= 5; $i++) {
                    $shared["cct_no_{$i}"] = 'CCT-' . $i . '-' . $identifierIndex;
                    $shared["bonder_no_{$i}"] = 'BND-' . $i . '-' . $identifierIndex;
                }
                break;

            case 'SHIELD':
                $shared['shield_no'] = 'SHD-' . str_pad($identifierIndex, 4, '0', STR_PAD_LEFT);
                $shared['address'] = 'ADDR-' . chr(65 + ($identifierIndex % 5));
                $shared['blade'] = 'BLADE-' . str_pad(rand(1, 10), 2, '0', STR_PAD_LEFT);
                $shared['cct_no_1'] = 'CCT-1-' . $identifierIndex;
                $shared['address_no_1_1'] = 'ADDR-1-' . $identifierIndex;
                $shared['cct_no_2'] = 'CCT-2-' . $identifierIndex;
                $shared['address_no_1_2'] = 'ADDR-2-' . $identifierIndex;
                for ($i = 1; $i <= 9; $i++) {
                    $shared["to_{$i}"] = 'TO-' . $i . '-' . chr(65 + (($identifierIndex + $i) % 5));
                }
                break;

            case 'DBL CRIMP':
                $shared['drawing_no'] = 'DRW-' . str_pad($identifierIndex, 4, '0', STR_PAD_LEFT);
                $shared['address'] = 'ADDR-' . chr(65 + ($identifierIndex % 5));
                $shared['to_machine'] = 'TO-MACH-' . rand(1, 10);
                for ($i = 1; $i <= 5; $i++) {
                    $shared["cct_no_{$i}"] = 'CCT-' . $i . '-' . $identifierIndex;
                    $shared["address_{$i}"] = 'ADDR-' . $i . '-' . chr(65 + (($identifierIndex + $i) % 5));
                }
                break;
        }

        return $shared;
    }

    /**
     * Generate shared data for a Circuit identifier group
     */
    protected function generateSharedCircuitData($identifierIndex)
    {
        return [
            'conveyor' => 'CONV-' . str_pad($identifierIndex, 3, '0', STR_PAD_LEFT),
            'cct_no' => 'CCT-' . str_pad($identifierIndex, 4, '0', STR_PAD_LEFT),
            'family' => 'FAM-' . chr(65 + ($identifierIndex % 5)),
            'qty' => rand(50, 500),
            'machine' => $this->machineList[($identifierIndex - 1) % count($this->machineList)],
            'released_note' => 'Note for group ' . $identifierIndex,
            'cust_no' => 'CUST-' . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT),
            'address' => 'ADDR-' . chr(65 + ($identifierIndex % 5)),
            'cct_code' => 'CODE-' . str_pad($identifierIndex, 3, '0', STR_PAD_LEFT),
            'kind' => 'TYPE-' . chr(65 + ($identifierIndex % 4)),
            'size' => rand(1, 10) . '.0',
            'col' => ['RED', 'BLUE', 'GREEN', 'BLACK', 'WHITE'][$identifierIndex % 5],
            'cl' => rand(100, 500),
            'terminal_1' => 'TERM-1-' . $identifierIndex,
            'note_1' => 'Note 1',
            'gold_1' => rand(0, 1) ? 'YES' : '',
            'strip_1' => rand(5, 15) . 'mm',
            'acc_1' => 'ACC-1-' . $identifierIndex,
            'acc_1a' => 'ACC-1A-' . $identifierIndex,
            'tube_1' => 'TUBE-1',
            'mark_1' => 'MK-1',
            'remark_1' => 'Remark 1 sample',
            'terminal_2' => 'TERM-2-' . $identifierIndex,
            'note_2' => 'Note 2',
            'gold_2' => rand(0, 1) ? 'YES' : '',
            'strip_2' => rand(5, 15) . 'mm',
            'acc_2' => 'ACC-2-' . $identifierIndex,
            'acc_2a' => 'ACC-2A-' . $identifierIndex,
            'tube_2' => 'TUBE-2',
            'mark_2' => 'MK-2',
            'remark_2' => 'Remark 2 sample',
            'ta' => 'TA-' . rand(1, 10),
            'tb' => 'TB-' . rand(1, 10),
            't01' => 'T01-' . rand(1, 5),
            't02' => 'T02-' . rand(1, 5),
            't03' => 'T03-' . rand(1, 5),
            't04' => 'T04-' . rand(1, 5),
            't05' => 'T05-' . rand(1, 5),
            't06' => 'T06-' . rand(1, 5),
        ];
    }

    /**
     * Generate row data for Shikake with shared identifier and unique barcodes/issue
     */
    protected function generateShikakeRowData($processType, $sharedData, $issueNum, $issueCount, $globalRowIndex)
    {
        $issue = $this->generateIssueFormat($issueNum, $issueCount);
        $uniqueSuffix = str_pad($globalRowIndex, 6, '0', STR_PAD_LEFT);

        // Base data (columns 0-8)
        $baseData = [
            $sharedData['conveyor'],           // Conveyor
            $sharedData['machine'],            // Machine
            $sharedData['qty'],                // QTY
            $issue,                            // Issue
            'BK-' . $uniqueSuffix,             // Barcode Kanban (unique)
            $sharedData['family'],             // Family
            $sharedData['released_date'],      // Released Date
            $sharedData['released_note'],      // Released Note
            $issueNum,                         // Sequence (reset per group)
        ];

        // Process-specific data
        switch ($processType) {
            case 'TWIST':
                return array_merge($baseData, [
                    $sharedData['cct_code'],
                    $sharedData['cct_no'],
                    $sharedData['machine_twist'],
                    $issueNum,                         // Sequence 2 (reset per group)
                    'BN-' . $uniqueSuffix,             // Barcode Navigasi (unique)
                    'BP-' . $uniqueSuffix,             // Barcode Process (unique)
                    'BS-' . $uniqueSuffix,             // Barcode Shikake (unique)
                    $sharedData['to_store'],
                    $sharedData['cust_no'],
                    $sharedData['kind'],
                    $sharedData['size'],
                    $sharedData['color'],
                    $sharedData['cl'],
                    $sharedData['terminal_a'],
                    $sharedData['acc_1_a'],
                    $sharedData['tube_a'],
                    $sharedData['note_a'],
                    $sharedData['strip_a'],
                    $sharedData['mark_a'],
                    $sharedData['terminal_b'],
                    $sharedData['acc_1_ab'],
                    $sharedData['tube_b'],
                    $sharedData['note_b'],
                    $sharedData['strip_b'],
                    $sharedData['mark_b'],
                ]);

            case 'BONDER':
                $data = [
                    $sharedData['bonder_no'],
                    $sharedData['address'],
                    $sharedData['dies'],
                    $sharedData['to_machine'],
                    'BN-' . $uniqueSuffix,             // Barcode Navigasi (unique)
                    'BP-' . $uniqueSuffix,             // Barcode Process (unique)
                ];
                for ($i = 1; $i <= 7; $i++) {
                    $data[] = $sharedData["cct_no_a_{$i}"];
                    $data[] = $sharedData["bonder_no_a_{$i}"];
                }
                for ($i = 1; $i <= 7; $i++) {
                    $data[] = $sharedData["cct_no_b_{$i}"];
                    $data[] = $sharedData["bonder_no_b_{$i}"];
                }
                return array_merge($baseData, $data);

            case 'JOINT':
                $data = [
                    $sharedData['bonder_no'],
                    $sharedData['address'],
                    $sharedData['address_store'],
                    $sharedData['to_machine'],
                    'BP-' . $uniqueSuffix,             // Barcode Process (unique)
                ];
                for ($i = 1; $i <= 5; $i++) {
                    $data[] = $sharedData["cct_no_{$i}"];
                    $data[] = $sharedData["bonder_no_{$i}"];
                }
                return array_merge($baseData, $data);

            case 'SHIELD':
                $data = [
                    $sharedData['shield_no'],
                    $sharedData['address'],
                    $sharedData['blade'],
                    $sharedData['cct_no_1'],
                    $sharedData['address_no_1_1'],
                    $sharedData['cct_no_2'],
                    $sharedData['address_no_1_2'],
                ];
                for ($i = 1; $i <= 9; $i++) {
                    $data[] = $sharedData["to_{$i}"];
                }
                return array_merge($baseData, $data);

            case 'DBL CRIMP':
                $data = [
                    $sharedData['drawing_no'],
                    $sharedData['address'],
                    'BM-' . $uniqueSuffix,             // Barcode Mesin (unique)
                    $sharedData['to_machine'],
                ];
                for ($i = 1; $i <= 5; $i++) {
                    $data[] = $sharedData["cct_no_{$i}"];
                    $data[] = $sharedData["address_{$i}"];
                }
                return array_merge($baseData, $data);

            default:
                return $baseData;
        }
    }

    /**
     * Generate row data for Circuit with shared identifier and unique barcodes/issue
     */
    protected function generateCircuitRowData($sharedData, $issueNum, $issueCount, $globalRowIndex)
    {
        $issue = $this->generateIssueFormat($issueNum, $issueCount);
        $uniqueSuffix = str_pad($globalRowIndex, 6, '0', STR_PAD_LEFT);

        return [
            $sharedData['conveyor'],           // Conveyor
            $sharedData['cct_no'],             // CCT No.
            $sharedData['family'],             // Family
            $sharedData['qty'],                // Qty.
            $issue,                            // Issue
            $sharedData['machine'],            // Machine
            $issueNum,                         // Sequence (reset per group)
            'BK-' . $uniqueSuffix,             // Barcode Kanban (unique)
            date('Y-m-d', strtotime('+' . rand(1, 30) . ' days')), // Released Date
            $sharedData['released_note'],      // Released Note
            $sharedData['cust_no'],            // Cust No.
            'BM-' . $uniqueSuffix,             // Barcode Mesin (unique)
            $sharedData['address'],            // Address
            $sharedData['cct_code'],           // CCT Code
            $sharedData['kind'],               // Kind
            $sharedData['size'],               // Size
            $sharedData['col'],                // Col
            $sharedData['cl'],                 // C/L
            $sharedData['terminal_1'],         // Terminal 1
            $sharedData['note_1'],             // Note 1
            $sharedData['gold_1'],             // Gold 1
            $sharedData['strip_1'],            // Strip 1
            $sharedData['acc_1'],              // Acc. 1
            $sharedData['acc_1a'],             // Acc. 1A
            $sharedData['tube_1'],             // Tube 1
            $sharedData['mark_1'],             // Mark 1
            $sharedData['remark_1'],           // Remark 1
            $sharedData['terminal_2'],         // Terminal 2
            $sharedData['note_2'],             // Note 2
            $sharedData['gold_2'],             // Gold 2
            $sharedData['strip_2'],            // Strip 2
            $sharedData['acc_2'],              // Acc 2
            $sharedData['acc_2a'],             // Acc 2A
            $sharedData['tube_2'],             // Tube 2
            $sharedData['mark_2'],             // Mark 2
            $sharedData['remark_2'],           // Remark 2
            $sharedData['ta'],                 // TA
            $sharedData['tb'],                 // TB
            $sharedData['t01'],                // T01
            $sharedData['t02'],                // T02
            $sharedData['t03'],                // T03
            $sharedData['t04'],                // T04
            $sharedData['t05'],                // T05
            $sharedData['t06'],                // T06
        ];
    }
}
