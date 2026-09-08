<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportHelper
{
    /**
     * Stream a simple one-sheet xlsx (header row + data rows) as a download.
     *
     * @param string $title     Sheet title / report title
     * @param array  $headers   Column headers
     * @param array  $rows      Rows, each an array of values in header order
     * @param string $filename  Downloaded file name
     */
    public static function download(string $title, array $headers, array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Excel sheet titles are capped at 31 chars and reject : \ / ? * [ ]
        $sheet->setTitle(substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $title), 0, 31));

        // Header row
        $sheet->fromArray($headers, null, 'A1');

        $lastColumn = Coordinate::stringFromColumnIndex(max(count($headers), 1));
        $headerRange = 'A1:' . $lastColumn . '1';

        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E1F2');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Data rows
        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $lastRow = count($rows) + 1;
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
