<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeHelper
{
    /**
     * Generate cached QR code - reuses existing if same content
     *
     * @param string $text Text to encode in QR code
     * @param string $subfolder Subfolder within cache directory
     * @return string|null Storage path to QR code image or null on failure
     */
    public static function generateQRCodeCached($text, $subfolder = 'qr')
    {
        if (empty($text)) {
            return null;
        }

        try {
            // Create hash-based filename for caching
            $hash = md5($text);
            $filename = "qr_{$hash}.png";
            $cachePath = "cache/{$subfolder}/{$filename}";
            $fullPath = storage_path("app/public/{$cachePath}");
            
            // Check if cached version exists
            if (file_exists($fullPath)) {
                return "/storage/{$cachePath}";
            }

            // Generate new QR code
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'scale' => 5,
                'imageTransparent' => false
            ]);
            $qrcode = new QRCode($options);
            $qrCodeDataUri = $qrcode->render($text);
            
            // Extract and save
            $qrCodeData = explode(',', $qrCodeDataUri)[1];
            $qrCodeBinary = base64_decode($qrCodeData);
            
            // Ensure directory exists
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            Storage::disk('public')->put($cachePath, $qrCodeBinary);
            
            return "/storage/{$cachePath}";
        } catch (\Exception $e) {
            Log::error('QR generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate cached barcode - reuses existing if same content
     *
     * @param string $data Data to encode in barcode
     * @param int $type Barcode type (default: CODE_128)
     * @param int $widthFactor Width factor for barcode
     * @param int $height Height of barcode in pixels
     * @param string $subfolder Subfolder within cache directory
     * @return string|null Storage path to barcode image or null on failure
     */
    public static function generateBarcodeCached($data, $type = null, $widthFactor = 3, $height = 80, $subfolder = 'barcode')
    {
        if (empty($data)) {
            return null;
        }

        try {
            // Create hash-based filename including generation parameters
            $generator = new BarcodeGeneratorPNG();
            $barcodeType = $type ?? $generator::TYPE_CODE_128;
            $hash = md5($data . $barcodeType . $widthFactor . $height);
            $filename = "barcode_{$hash}.png";
            $cachePath = "cache/{$subfolder}/{$filename}";
            $fullPath = storage_path("app/public/{$cachePath}");
            
            // Check if cached version exists
            if (file_exists($fullPath)) {
                return "/storage/{$cachePath}";
            }

            // Generate new barcode
            $barcode = $generator->getBarcode($data, $barcodeType, $widthFactor, $height);
            
            // Ensure directory exists
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            Storage::disk('public')->put($cachePath, $barcode);
            
            return "/storage/{$cachePath}";
        } catch (\Exception $e) {
            Log::error('Barcode generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate QR code and save to storage
     *
     * @param string $text Text to encode in QR code
     * @param int $circuitId Circuit ID for unique filename
     * @return string|null Storage path to QR code image or null on failure
     */
    public static function generateQRCode($text, $circuitId)
    {
        if (empty($text)) {
            return null;
        }

        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'scale' => 5,
                'imageTransparent' => false
            ]);
            $qrcode = new QRCode($options);
            $qrCodeDataUri = $qrcode->render($text);
            
            // Extract base64 data from data URI
            $qrCodeData = explode(',', $qrCodeDataUri)[1];
            $qrCodeBinary = base64_decode($qrCodeData);
            
            $qrPath = 'temp/qr_' . $circuitId . '_' . time() . rand(1000, 9999) . '.png';
            Storage::disk('public')->put($qrPath, $qrCodeBinary);
            
            return '/storage/' . $qrPath;
        } catch (\Exception $e) {
            Log::error('QR generation failed for circuit ' . $circuitId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate barcode and save to storage
     *
     * @param string $data Data to encode in barcode
     * @param int $circuitId Circuit ID for unique filename
     * @param int $type Barcode type (default: CODE_128)
     * @param int $widthFactor Width factor for barcode
     * @param int $height Height of barcode in pixels
     * @return string|null Storage path to barcode image or null on failure
     */
    public static function generateBarcode($data, $circuitId, $type = null, $widthFactor = 3, $height = 80)
    {
        if (empty($data)) {
            return null;
        }

        try {
            $generator = new BarcodeGeneratorPNG();
            $barcodeType = $type ?? $generator::TYPE_CODE_128;
            $barcode = $generator->getBarcode($data, $barcodeType, $widthFactor, $height);
            $barcodePath = 'temp/barcode_' . $circuitId . '_' . time() . rand(1000, 9999) . '.png';
            Storage::disk('public')->put($barcodePath, $barcode);
            
            return '/storage/' . $barcodePath;
        } catch (\Exception $e) {
            Log::error('Barcode generation failed for circuit ' . $circuitId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean old cached barcode files (optional maintenance method)
     *
     * @param int $maxAge Maximum age in seconds (default: 7 days)
     * @return int Number of files cleaned
     */
    public static function cleanOldCache($maxAge = 604800) // 7 days
    {
        $cleaned = 0;
        $cacheDir = storage_path('app/public/cache');
        
        if (!is_dir($cacheDir)) {
            return $cleaned;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && 
                (strpos($file->getFilename(), 'qr_') === 0 || strpos($file->getFilename(), 'barcode_') === 0)) {
                
                if (time() - $file->getMTime() > $maxAge) {
                    unlink($file->getPathname());
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }

    /**
     * Generate both QR code and barcode for a circuit with caching optimization
     *
     * @param object $circuit Circuit object with necessary fields
     * @param string $qrTextField Field name for QR code text (default: 'barcode_kanban')
     * @param string $qrFallbackField Fallback field if primary is empty (default: 'cct_no')
     * @param string $barcodeField Field name for barcode data (default: 'machine')
     * @param string $barcodeFallbackField Fallback field if primary is empty (default: 'cct_code')
     * @return void Modifies circuit object by adding qr_code_path and barcode_path properties
     */
    public static function generateCircuitBarcodes($circuit, $qrTextField = 'barcode_kanban', $qrFallbackField = 'cct_no', $barcodeField = 'barcode_mesin', $barcodeFallbackField = 'cct_code')
    {
        // Generate QR Code using cached method
        $qrText = !empty($circuit->$qrTextField) ? $circuit->$qrTextField : ($circuit->$qrFallbackField ?? '');
        if (!empty($qrText)) {
            $qrPath = self::generateQRCodeCached($qrText, 'circuit');
            if ($qrPath) {
                $circuit->qr_code_path = $qrPath;
            }
        }

        // Generate Barcode using cached method
        $barcodeData = !empty($circuit->$barcodeField) ? $circuit->$barcodeField : ($circuit->$barcodeFallbackField ?? '');
        if (!empty($barcodeData)) {
            $barcodePath = self::generateBarcodeCached($barcodeData, null, 3, 80, 'circuit');
            if ($barcodePath) {
                $circuit->barcode_path = $barcodePath;
            }
        }
    }
}
