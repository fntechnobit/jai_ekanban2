<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Config\ShikakeTemplateConfig;
use App\Config\CircuitTemplateConfig;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GenerateTemplates extends Command
{
    protected $signature = 'templates:generate {--type=all : Type of templates to generate (all, circuit, shikake)}';
    protected $description = 'Generate Excel templates for Shikake and Circuit imports';

    public function handle()
    {
        $type = strtolower($this->option('type'));

        if (!in_array($type, ['all', 'circuit', 'shikake'])) {
            $this->error("Invalid type: {$type}. Use 'all', 'circuit', or 'shikake'.");
            return Command::FAILURE;
        }

        $this->info('Generating templates...');
        $docsPath = public_path('docs');

        // Ensure docs directory exists
        if (!is_dir($docsPath)) {
            mkdir($docsPath, 0755, true);
        }

        $generatedCount = 0;

        // Generate Shikake templates
        if ($type === 'all' || $type === 'shikake') {
            $this->info('');
            $this->info('Generating Shikake templates...');
            $templates = ShikakeTemplateConfig::getAllTemplates();

            foreach ($templates as $filename => $headers) {
                $this->createTemplate($docsPath . '/' . $filename, $headers);
                $this->info("  Created: {$filename}");
                $generatedCount++;
            }
        }

        // Generate Circuit templates
        if ($type === 'all' || $type === 'circuit') {
            $this->info('');
            $this->info('Generating Circuit templates...');
            $templates = CircuitTemplateConfig::getAllTemplates();

            foreach ($templates as $filename => $headers) {
                $this->createCircuitTemplate($docsPath . '/' . $filename, $headers);
                $this->info("  Created: {$filename}");
                $generatedCount++;
            }
        }

        $this->info('');
        $this->info("All {$generatedCount} template(s) generated successfully!");
        $this->info("Files saved to: {$docsPath}");

        return Command::SUCCESS;
    }

    protected function createTemplate($filePath, $headers)
    {
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

        // Add example assy columns
        $exampleAssyColumns = ['ASSY-001', 'ASSY-002', 'ASSY-003'];
        $assyStartCol = $col;
        foreach ($exampleAssyColumns as $assyHeader) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $assyHeader);
            $col++;
        }

        // Style required header columns (blue background)
        $lastRequiredColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $requiredHeaderRange = "A1:{$lastRequiredColumn}1";

        $sheet->getStyle($requiredHeaderRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
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

        // Style example assy columns (light green background)
        $firstAssyColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol);
        $lastAssyColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($assyStartCol + count($exampleAssyColumns) - 1);
        $assyHeaderRange = "{$firstAssyColumn}1:{$lastAssyColumn}1";

        $sheet->getStyle($assyHeaderRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'italic' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '92D050'], // Light green
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

        // Auto-size all columns (required + assy)
        $totalColumns = count($headers) + count($exampleAssyColumns);
        foreach (range(1, $totalColumns) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Write the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    protected function createCircuitTemplate($filePath, $headers)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Get color categories
        $cuttingTwistColumns = CircuitTemplateConfig::getCuttingTwistColumns();
        $cuttingOnlyColumns = CircuitTemplateConfig::getCuttingOnlyColumns();

        // Set required headers with color coding
        $col = 1;
        foreach ($headers as $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $header);

            // Determine color based on column type
            if (in_array($header, $cuttingTwistColumns)) {
                // Blue for CUTTING_TWIST only
                $bgColor = 'BDD7EE';
                $fontColor = '000000';
            } elseif (in_array($header, $cuttingOnlyColumns)) {
                // Yellow for CUTTING only
                $bgColor = 'FFEB9C';
                $fontColor = '000000';
            } else {
                // Green for ALL types
                $bgColor = 'C6EFCE';
                $fontColor = '000000';
            }

            $sheet->getStyle($colLetter . '1')->applyFromArray([
                'font' => [
                    'bold' => true,
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

            $col++;
        }

        // Add example assy columns
        $exampleAssyColumns = ['ASSY-001', 'ASSY-002', 'ASSY-003'];
        $assyStartCol = $col;
        foreach ($exampleAssyColumns as $assyHeader) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $assyHeader);

            // Style assy columns (light green with italic)
            $sheet->getStyle($colLetter . '1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'italic' => true,
                    'color' => ['rgb' => '000000'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '92D050'],
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

            $col++;
        }

        // Auto-size all columns
        $totalColumns = count($headers) + count($exampleAssyColumns);
        foreach (range(1, $totalColumns) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Create PETUNJUK (Instructions) sheet
        $petunjukSheet = $spreadsheet->createSheet();
        $petunjukSheet->setTitle('PETUNJUK');

        $instructions = [
            ['PETUNJUK PENGISIAN TEMPLATE MASTER CIRCUIT'],
            [''],
            ['1. CARA MENGISI KOLOM TYPE'],
            ['   - Isi dengan "CUTTING" untuk data cutting standar'],
            ['   - Isi dengan "CUTTING_TWIST" untuk data cutting twist'],
            ['   - Kolom ini WAJIB diisi untuk setiap baris data'],
            ['   - Penulisan harus HURUF KAPITAL semua'],
            [''],
            ['2. KETERANGAN WARNA'],
            [''],
            ['   HIJAU - Field yang WAJIB diisi untuk SEMUA tipe'],
            ['   - TYPE, CARLINE, CONVEYOR, CCT No., FAMILY, QTY, MACHINE, SEQUENCE'],
            ['   - RELEASED NOTE, CUST NO., ADDRESS, CCT CODE, KIND, SIZE, COL, C/L'],
            ['   - TERMINAL 1, TERMINAL 2, NOTE 1, NOTE 2, STRIP 1, STRIP 2'],
            ['   - ACC. 1B, ACC 2B, TUBE 1, TUBE 2, MARK 1, MARK 2'],
            ['   - TO STORE, T01-T03'],
            [''],
            ['   KUNING - Field KHUSUS untuk tipe CUTTING saja'],
            ['   - BARCODE MESIN'],
            ['   - GOLD 1, GOLD 2'],
            ['   - ACC. 1A, ACC 2A'],
            ['   - Jika TYPE = "CUTTING_TWIST", kolom ini bisa dikosongkan'],
            [''],
            ['   BIRU - Field KHUSUS untuk tipe CUTTING_TWIST saja'],
            ['   - MACHINE TWIST - Nomor mesin twist'],
            ['   - SEQUENCE 2 - Urutan kedua'],
            ['   - BARCODE NAVIGASI - Barcode untuk navigasi'],
            ['   - BARCODE PROCESS - Barcode proses'],
            ['   - BARCODE SHIKAKE - Barcode shikake'],
            ['   - MEMORY TWIST - Memory twist value'],
            ['   - Jika TYPE = "CUTTING", kolom ini bisa dikosongkan'],
            [''],
            ['3. CONTOH PENGISIAN'],
            [''],
            ['   Contoh baris CUTTING:'],
            ['   TYPE=CUTTING | CARLINE=ABC | CONVEYOR=CONV-1 | ... | BARCODE MESIN=BM001 | (kolom biru dikosongkan)'],
            [''],
            ['   Contoh baris CUTTING_TWIST:'],
            ['   TYPE=CUTTING_TWIST | CARLINE=XYZ | CONVEYOR=CONV-2 | ... | MACHINE TWIST=MT001 | BARCODE NAVIGASI=NAV001'],
            [''],
            ['4. CATATAN PENTING'],
            ['   - Satu file Excel bisa berisi campuran data CUTTING dan CUTTING_TWIST'],
            ['   - Pastikan TYPE diisi dengan benar di setiap baris'],
            ['   - Maksimal 1000 baris per upload'],
            ['   - Format file harus .xlsx atau .xls'],
            ['   - Baris data dimulai dari baris ke-2 (baris 1 adalah header)'],
            [''],
            ['5. KOLOM ASSY'],
            ['   - Kolom Assy dimulai setelah kolom T03'],
            ['   - Isi dengan angka "1" jika circuit memiliki assy tersebut'],
            ['   - Kosongkan jika circuit tidak memiliki assy tersebut'],
        ];

        $row = 1;
        foreach ($instructions as $line) {
            $petunjukSheet->setCellValue('A' . $row, $line[0]);
            $row++;
        }

        // Style title
        $petunjukSheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
        ]);

        // Add color legend
        $legendRow = $row + 1;
        $petunjukSheet->setCellValue('A' . $legendRow, 'LEGENDA WARNA:');
        $petunjukSheet->getStyle('A' . $legendRow)->getFont()->setBold(true);

        $legendRow++;
        $petunjukSheet->setCellValue('A' . $legendRow, 'HIJAU');
        $petunjukSheet->getStyle('A' . $legendRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');
        $petunjukSheet->setCellValue('B' . $legendRow, '= Wajib untuk SEMUA tipe');

        $legendRow++;
        $petunjukSheet->setCellValue('A' . $legendRow, 'KUNING');
        $petunjukSheet->getStyle('A' . $legendRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFEB9C');
        $petunjukSheet->setCellValue('B' . $legendRow, '= Khusus CUTTING saja');

        $legendRow++;
        $petunjukSheet->setCellValue('A' . $legendRow, 'BIRU');
        $petunjukSheet->getStyle('A' . $legendRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');
        $petunjukSheet->setCellValue('B' . $legendRow, '= Khusus CUTTING_TWIST saja');

        // Set column widths for PETUNJUK sheet
        $petunjukSheet->getColumnDimension('A')->setWidth(100);
        $petunjukSheet->getColumnDimension('B')->setWidth(30);

        // Set Data sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        // Disable formula pre-calculation to avoid issues
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($filePath);
    }
}
