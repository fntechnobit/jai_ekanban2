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
     * Generate both QR code and barcode for a circuit
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
        // Generate QR Code
        $qrText = !empty($circuit->$qrTextField) ? $circuit->$qrTextField : ($circuit->$qrFallbackField ?? '');
        if (!empty($qrText)) {
            $qrPath = self::generateQRCode($qrText, $circuit->id);
            if ($qrPath) {
                $circuit->qr_code_path = $qrPath;
            }
        }

        // Generate Barcode
        $barcodeData = !empty($circuit->$barcodeField) ? $circuit->$barcodeField : ($circuit->$barcodeFallbackField ?? '');
        if (!empty($barcodeData)) {
            $barcodePath = self::generateBarcode($barcodeData, $circuit->id);
            if ($barcodePath) {
                $circuit->barcode_path = $barcodePath;
            }
        }
    }
}
