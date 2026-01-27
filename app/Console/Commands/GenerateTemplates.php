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
                $this->createTemplate($docsPath . '/' . $filename, $headers);
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
}
