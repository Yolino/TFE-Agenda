<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileCompressionService
{
    public const MAX_IMAGE_DIMENSION = 1920;
    public const JPEG_QUALITY = 75;
    public const PNG_COMPRESSION = 7;

    public function storeAndCompress(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);

        if (! $isImage) {
            return $file->store($directory, $disk);
        }

        $compressed = $this->compressImage($file->getRealPath(), $extension);

        $filename = $directory . '/' . Str::random(40) . '.' . ($extension === 'png' ? 'png' : 'jpg');
        Storage::disk($disk)->put($filename, $compressed);

        return $filename;
    }

    private function compressImage(string $path, string $extension): string
    {
        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            default => false,
        };

        if ($image === false) {
            return file_get_contents($path);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxDim = self::MAX_IMAGE_DIMENSION;

        if ($width > $maxDim || $height > $maxDim) {
            $ratio = min($maxDim / $width, $maxDim / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($extension === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        if ($extension === 'png') {
            imagepng($image, null, self::PNG_COMPRESSION);
        } else {
            imagejpeg($image, null, self::JPEG_QUALITY);
        }
        $binary = ob_get_clean();

        imagedestroy($image);

        return $binary;
    }
}
