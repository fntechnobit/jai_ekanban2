<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Resize and save image by height only (width auto-calculated)
     * Optimized for thermal printer
     * Height set to match print area, width maintains original aspect ratio
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $path
     * @param int $height Target height in pixels (default 576px = 80mm at 203 DPI, matches print area)
     * @return string filename
     */
    public static function resizeAndSave($image, $path, $height = 576)
    {
        // Create directory if not exists
        $fullPath = public_path($path);
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Generate unique filename
        $filename = time() . '_' . $image->getClientOriginalName();
        $destination = $fullPath . '/' . $filename;

        // Get image info
        $imageInfo = getimagesize($image->getRealPath());
        $mime = $imageInfo['mime'];

        // Create image resource based on mime type
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($image->getRealPath());
                break;
            case 'image/png':
                $source = imagecreatefrompng($image->getRealPath());
                break;
            case 'image/gif':
                $source = imagecreatefromgif($image->getRealPath());
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($image->getRealPath());
                break;
            default:
                throw new \Exception('Unsupported image type: ' . $mime);
        }

        // Get original dimensions
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);

        // Calculate width based on aspect ratio and target height
        $aspectRatio = $originalWidth / $originalHeight;
        $newHeight = $height;
        $newWidth = (int)($height * $aspectRatio);

        // Create canvas with calculated dimensions (no padding/border)
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            // Fill with white background for JPEG
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        }

        // Resize and copy to canvas (no offset, full canvas usage)
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Enhance contrast for thermal printer clarity (makes thin lines bolder)
        imagefilter($canvas, IMG_FILTER_CONTRAST, -30);
        imagefilter($canvas, IMG_FILTER_BRIGHTNESS, -5);

        // Always save as PNG for maximum quality (no lossy compression)
        // Change extension to .png regardless of original format
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.png';
        $destination = $fullPath . '/' . $filename;
        imagepng($canvas, $destination, 6);

        // Free memory
        imagedestroy($source);
        imagedestroy($canvas);

        return $filename;
    }
}
