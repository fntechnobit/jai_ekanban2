<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportHelper
{
    /**
     * Clean and normalize a string value from Excel
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function cleanValue($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return trim((string) $value);
    }

    /**
     * Clean and convert a numeric value from Excel
     * 
     * @param mixed $value
     * @return int|null
     */
    public static function cleanNumeric($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Clean and convert a date value from Excel
     * Handles Excel date serial numbers and various string date formats
     * 
     * @param mixed $value
     * @return string|null Date in Y-m-d format
     */
    public static function cleanDate($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        try {
            // Handle Excel date serial numbers
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            
            // Handle string dates with multiple formats
            $formats = [
                'd/m/y',     // 31/12/25
                'd/m/Y',     // 31/12/2025
                'd-m-y',     // 31-12-25
                'd-m-Y',     // 31-12-2025
                'Y-m-d',     // 2025-12-31
                'm/d/Y',     // 12/31/2025
                'm/d/y',     // 12/31/25
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            }
            
            // Fallback to strtotime
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::warning("Failed to parse date: {$value}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convert column number to Excel column letter (A, B, C, ..., Z, AA, AB, ...)
     * 
     * @param int $number Column number (1-based)
     * @return string Column letter
     */
    public static function numberToColumnLetter($number)
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intdiv($number, 26);
        }
        return $letter;
    }
}
