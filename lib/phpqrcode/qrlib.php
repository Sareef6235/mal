<?php

declare(strict_types=1);

final class QRcode
{
    public static function png(string $text, string $file): void
    {
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . rawurlencode($text);
        $img = @file_get_contents($url);

        if ($img !== false) {
            file_put_contents($file, $img);
            return;
        }

        if (function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor(250, 250);
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0);
            imagefill($im, 0, 0, $white);
            imagestring($im, 5, 10, 115, $text, $black);
            imagepng($im, $file);
            imagedestroy($im);
            return;
        }

        throw new RuntimeException('QR generation failed.');
    }
}
