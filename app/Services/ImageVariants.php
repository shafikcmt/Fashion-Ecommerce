<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Creates smaller WebP copies of an uploaded photograph with GD so product grids download far fewer bytes.
 * The original upload is never changed. If GD cannot read the file or lacks WebP support, no variants
 * are produced and the storefront simply uses the original.
 */
final class ImageVariants
{
    public const WIDTHS = [480, 960];

    /**
     * @return array{width: int, files: array<int, string>}|null
     */
    public function generate(string $disk, string $path): ?array
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }
        $contents = Storage::disk($disk)->get($path);
        $image = $contents === null ? false : @imagecreatefromstring($contents);
        if ($image === false) {
            return null;
        }
        $width = imagesx($image);
        $files = [];
        foreach (self::WIDTHS as $target) {
            if ($width <= $target) {
                continue;
            }
            $scaled = imagescale($image, $target, -1, IMG_BICUBIC);
            if ($scaled === false) {
                continue;
            }
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            ob_start();
            $written = imagewebp($scaled, null, 82);
            $data = (string) ob_get_clean();
            if ($written && $data !== '') {
                $variantPath = preg_replace('/\.[A-Za-z0-9]+$/', '', $path).'-'.$target.'.webp';
                Storage::disk($disk)->put($variantPath, $data);
                $files[$target] = $variantPath;
            }
        }

        return $files === [] ? null : ['width' => $width, 'files' => $files];
    }
}
