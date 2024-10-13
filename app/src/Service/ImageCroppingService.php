<?php

namespace App\Service;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function imagecreatefromjpeg;

class ImageCroppingService
{
    public function __construct(
        #[Autowire('%app_image_sizes%')]
        private array $sizes,
    )
    {
        $this->prepareSizes();
    }

    public function createSizes(string $filename, string $baseDir): array
    {
        $result = [];

        foreach ($this->sizes as $size) {
            try {
                $result[] = $this->resize($filename, $baseDir, $size['width'], $size['height']);
            } catch (Exception) {
                continue;
            }
        }

        return $result;
    }

    private function resize(string $filename, string $baseDir, int $width, int $height): array
    {
        $path = $baseDir . $filename;
        $origImage = imagecreatefromjpeg($path);
        $origWidth = imagesx($origImage);
        $origHeight = imagesy($origImage);

        // Calculate aspect ratio
        $aspectRatio = $origWidth / $origHeight;

        // Calculate new dimensions
        if ($width / $height > $aspectRatio) {
            $newWidth = (int)($height * $aspectRatio);
            $newHeight = $height;
        } else {
            $newWidth = $width;
            $newHeight = (int)($width / $aspectRatio);
        }

        $resizedImage = imagescale($origImage, $newWidth, $newHeight);

        if ($resizedImage === false) {
            throw new RuntimeException('Could not resize image');
        }

        $resizedFilename = pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . 'x' . $height . '.' . pathinfo($path, PATHINFO_EXTENSION);
        imagejpeg($resizedImage, $baseDir . $resizedFilename);

        return [
            'path' => $resizedFilename,
            'width' => $newWidth,
            'height' => $newHeight
        ];
    }

    private function crop(string $filename, string $baseDir, int $width, int $height): array
    {
        $path = $baseDir . $filename;
        $origImage = imagecreatefromjpeg($path);
        $origWith = imagesx($origImage);
        $origHeight = imagesy($origImage);

        if ($origWith < $width || $origHeight < $height) {
            throw new InvalidArgumentException('Image is too small to crop');
        }

        $cropX = ($origWith / 2) - ($width / 2);
        $cropY = ($origHeight / 2) - ($height / 2);
        $croppedImage = imagecrop($origImage, ['x' => $cropX, 'y' => $cropY, 'width' => $width, 'height' => $height]);

        if ($croppedImage === false) {
            throw new RuntimeException('Could not crop image');
        }

        $croppedFilename = pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . 'x' . $height . '.' . pathinfo($path, PATHINFO_EXTENSION);
        imagejpeg($croppedImage, $baseDir . $croppedFilename);

        return [
            'path' => $croppedFilename,
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * @return void
     */
    private function prepareSizes(): void
    {
        $this->sizes = array_map(function ($size) {

            if (empty($size) || empty($size[0]) || empty($size[1]) || !is_int($size[0]) || !is_int($size[1])) {
                throw new InvalidArgumentException('Invalid size format: ' . $size);
            }

            return [
                'width' => $size[0],
                'height' => $size[1]
            ];
        }, $this->sizes);
    }
}