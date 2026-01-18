<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Resize and save image
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $path
     * @param int $width
     * @param int $height
     * @return string filename
     */
    public static function resizeAndSave($image, $path, $width = 400, $height = 233)
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

        // Calculate aspect ratio
        $aspectRatio = $originalWidth / $originalHeight;
        $targetAspectRatio = $width / $height;

        // Calculate new dimensions maintaining aspect ratio
        if ($aspectRatio > $targetAspectRatio) {
            // Image is wider
            $newWidth = $width;
            $newHeight = (int)($width / $aspectRatio);
        } else {
            // Image is taller
            $newHeight = $height;
            $newWidth = (int)($height * $aspectRatio);
        }

        // Create blank canvas with target dimensions
        $canvas = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG and GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        } else {
            // Fill with white background for JPEG
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        }

        // Calculate position to center the image
        $offsetX = (int)(($width - $newWidth) / 2);
        $offsetY = (int)(($height - $newHeight) / 2);

        // Resize and copy to canvas
        imagecopyresampled(
            $canvas,
            $source,
            $offsetX,
            $offsetY,
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
