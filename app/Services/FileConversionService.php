<?php
namespace App\Services;

class FileConversionService
{
    public function supportedFormats(): array
    {
        return [
            'images' => ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'],
            'documents' => ['pdf', 'docx', 'txt', 'html'],
            'video' => ['mp4', 'mov', 'avi', 'mkv'],
            'audio' => ['mp3', 'wav', 'aac'],
        ];
    }

    public function cleanupExpired(string $directory, int $maxAgeMinutes = 30): int
    {
        $deleted = 0;
        foreach (glob(rtrim($directory, '/') . '/*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < strtotime("-{$maxAgeMinutes} minutes")) {
                unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }
}
