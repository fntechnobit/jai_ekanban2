<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\Types\TypeCode39;

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
                return asset("storage/{$cachePath}");
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
            
            return asset("storage/{$cachePath}");
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
                return asset("storage/{$cachePath}");
            }

            // Generate new barcode
            $barcode = $generator->getBarcode($data, $barcodeType, $widthFactor, $height);
            
            // Ensure directory exists
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            Storage::disk('public')->put($cachePath, $barcode);
            
            return asset("storage/{$cachePath}");
        } catch (\Exception $e) {
            Log::error('Barcode generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lebar bar sempit / lebar Code 39 dalam dot printer (1px = 1 dot @203dpi).
     *
     * Picqer hanya bisa rasio 3:1 dengan lebar bar bilangan bulat, sehingga
     * pilihannya cuma 2/6 dot (celah 2 dot tertutup dot bleed thermal) atau 3/9
     * dot (terlalu lebar untuk cell). 3/7 (rasio 2.33, masih dalam spesifikasi
     * Code 39 yaitu 2.0-3.0 dan >= 2.2 untuk X < 0.5mm) menyamai ukuran barcode
     * Code 39 referensi dari user (~0.3-0.37mm per modul): bar sempit 0.375mm
     * cukup tebal untuk tahan dot bleed, total 5 karakter ~36mm.
     * JANGAN render PNG ini lalu diperkecil/diperbesar via CSS - harus tampil 1:1.
     */
    const CODE39_NARROW = 3;
    const CODE39_WIDE = 7;

    /** Quiet zone minimum Code 39 = 10x bar sempit, disediakan oleh padding cell. */
    const CODE39_QUIET_ZONE = 30;

    /** Tinggi bar default ~11mm, sama dengan barcode Code 39 referensi user. */
    const CODE39_HEIGHT = 88;

    /**
     * Normalisasi data untuk Code 39: trim, buang tanda '*' (start/stop Code 39 -
     * kalau ikut di-encode scanner membaca stop di awal dan data jadi kosong),
     * lalu huruf besar. Mengembalikan null bila ada karakter di luar set Code 39.
     */
    public static function sanitizeCode39($data)
    {
        $clean = strtoupper(trim(trim((string) $data), '*'));
        $clean = trim($clean);

        if ($clean === '' || !preg_match('/^[A-Z0-9 .$\/+%-]+$/', $clean)) {
            return null;
        }

        return $clean;
    }

    /**
     * Lebar PNG Code 39 (dot) tanpa quiet zone, untuk menghitung lebar cell.
     * Tiap karakter = 3 bar lebar + 6 bar sempit, antar karakter dipisah 1 celah
     * sempit, ditambah karakter start/stop '*' di kedua ujung.
     */
    public static function code39Width($data, $narrow = self::CODE39_NARROW, $wide = self::CODE39_WIDE)
    {
        $clean = self::sanitizeCode39($data);
        if ($clean === null) {
            return 0;
        }

        $symbols = strlen($clean) + 2;

        return $symbols * (3 * $wide + 6 * $narrow) + ($symbols - 1) * $narrow;
    }

    /**
     * Generate cached Code 39 barcode PNG dengan lebar bar sempit/lebar sendiri.
     *
     * Pola bar diambil dari picqer (TypeCode39, modul 1 = sempit, 3 = lebar),
     * lalu digambar ulang dengan GD memakai lebar dot $narrow/$wide. PNG tidak
     * punya margin putih - quiet zone harus datang dari padding cell.
     *
     * @return string|null URL gambar, atau null bila data kosong / tidak valid
     */
    public static function generateCode39Cached($data, $height = self::CODE39_HEIGHT, $subfolder = 'barcode', $narrow = self::CODE39_NARROW, $wide = self::CODE39_WIDE)
    {
        $clean = self::sanitizeCode39($data);
        if ($clean === null) {
            if (!empty(trim((string) $data))) {
                Log::warning('Code39: data tidak valid, barcode tidak dibuat: ' . $data);
            }
            return null;
        }

        try {
            $hash = md5('C39|' . $clean . '|' . $narrow . '|' . $wide . '|' . $height);
            $cachePath = "cache/{$subfolder}/barcode39_{$hash}.png";
            $fullPath = storage_path("app/public/{$cachePath}");

            if (file_exists($fullPath)) {
                return asset("storage/{$cachePath}");
            }

            $bars = (new TypeCode39())->getBarcode($clean)->getBars();
            // picqer menambahkan celah antar-karakter setelah '*' terakhir juga;
            // buang supaya PNG berakhir tepat di bar terakhir.
            if (!empty($bars) && !end($bars)->isBar()) {
                array_pop($bars);
            }

            $toDots = fn($bar) => $bar->getWidth() > 1 ? $wide : $narrow;
            $width = array_sum(array_map($toDots, $bars));

            $img = imagecreate($width, $height);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefill($img, 0, 0, $white);

            $x = 0;
            foreach ($bars as $bar) {
                $w = $toDots($bar);
                if ($bar->isBar()) {
                    imagefilledrectangle($img, $x, 0, $x + $w - 1, $height - 1, $black);
                }
                $x += $w;
            }

            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);

            Storage::disk('public')->put($cachePath, $png);

            return asset("storage/{$cachePath}");
        } catch (\Exception $e) {
            Log::error('Code39 generation failed: ' . $e->getMessage());
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
            
            return asset('storage/' . $qrPath);
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
            
            return asset('storage/' . $barcodePath);
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

        // Generate Barcode (Code 39) using cached method
        $barcodeData = !empty($circuit->$barcodeField) ? $circuit->$barcodeField : ($circuit->$barcodeFallbackField ?? '');
        if (!empty($barcodeData)) {
            $barcodePath = self::generateCode39Cached($barcodeData, self::CODE39_HEIGHT, 'circuit');
            if ($barcodePath) {
                $circuit->barcode_path = $barcodePath;
                $circuit->barcode_data = self::sanitizeCode39($barcodeData);
                $circuit->barcode_width = self::code39Width($barcodeData);
            }
        }
    }
}
