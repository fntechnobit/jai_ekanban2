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
     * @param int $height Target height in pixels (default 450px for ~57mm at 203 DPI)
     * @return string filename
     */
    public static function resizeAndSave($image, $path, $height = 450)
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

        // Save resized image
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($canvas, $destination, 90);
                break;
            case 'image/png':
                imagepng($canvas, $destination, 9);
                break;
            case 'image/gif':
                imagegif($canvas, $destination);
                break;
            case 'image/webp':
                imagewebp($canvas, $destination, 90);
                break;
        }

        // Free memory
        imagedestroy($source);
        imagedestroy($canvas);

        return $filename;
    }
}
