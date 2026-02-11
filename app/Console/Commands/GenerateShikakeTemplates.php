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

        // Set headers
        $col = 1;
        foreach ($headers as $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $header);
            $col++;
        }

        // Style the header row
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastColumn}1";

        $sheet->getStyle($headerRange)->applyFromArray([
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

        // Auto-size columns
        foreach (range(1, count($headers)) as $colIndex) {
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
