<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Config\ShikakeTemplateConfig;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GenerateShikakeTemplates extends Command
{
    protected $signature = 'shikake:generate-templates';
    protected $description = 'Generate Excel templates for Shikake process types';

    public function handle()
    {
        $this->info('Generating Shikake templates...');

        $templates = ShikakeTemplateConfig::getAllTemplates();
        $docsPath = public_path('docs');

        foreach ($templates as $filename => $headers) {
            $this->createTemplate($docsPath . '/' . $filename, $headers);
            $this->info("Created: {$filename}");
        }

        $this->info('All templates generated successfully!');
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
}
