<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Exception;

/**
 * Image Processor Service
 *
 * Provides image manipulation functionality using native PHP GD extension.
 * No external packages required - uses built-in PHP image processing functions.
 *
 * This service replaces packages like intervention/image with native implementations.
 *
 * Supported formats: JPEG, PNG, GIF, WebP
 * Features:
 * - Resize (maintain aspect ratio or exact dimensions)
 * - Convert between formats
 * - Generate thumbnails
 * - Add watermarks
 * - Crop images
 * - Quality control
 *
 * Usage:
 * $processor = new ImageProcessor();
 * $processor->resize($inputPath, $outputPath, 800, 600);
 * $processor->convertToWebP($inputPath, $outputPath);
 * $processor->watermark($basePath, $watermarkPath, $outputPath);
 */
class ImageProcessor
{
    /**
     * Supported MIME types for image processing.
     */
    private const SUPPORTED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Resize image maintaining aspect ratio.
     *
     * @param  string  $inputPath  Path to input image
     * @param  string  $outputPath  Path to save resized image
     * @param  int  $width  Target width
     * @param  int  $height  Target height (optional, maintains aspect ratio if null)
     * @param  int  $quality  Quality for JPEG/WebP (0-100)
     * @return bool Success status
     *
     * @throws Exception If image type is unsupported or file operations fail
     */
    public function resize(
        string $inputPath,
        string $outputPath,
        int $width,
        ?int $height = null,
        int $quality = 85
    ): bool {
        $this->validateImageFile($inputPath);

        $info = getimagesize($inputPath);
        $mime = $info['mime'];

        // Create source image
        $source = $this->createImageFromFile($inputPath, $mime);

        // Calculate dimensions if height not provided
        if ($height === null) {
            $originalWidth = imagesx($source);
            $originalHeight = imagesy($source);
            $height = (int) (($width / $originalWidth) * $originalHeight);
        }

        // Resize image
        $resized = imagescale($source, $width, $height);

        // Save image
        $result = $this->saveImage($resized, $outputPath, $mime, $quality);

        // Free memory
        imagedestroy($source);
        imagedestroy($resized);

        return $result;
    }

    /**
     * Create thumbnail from image.
     *
     * @param  string  $inputPath  Path to input image
     * @param  string  $outputPath  Path to save thumbnail
     * @param  int  $width  Thumbnail width
     * @param  int  $height  Thumbnail height
     * @param  bool  $crop  Whether to crop to exact dimensions
     * @param  int  $quality  Quality for JPEG/WebP (0-100)
     * @return bool Success status
     *
     * @throws Exception If image type is unsupported or file operations fail
     */
    public function thumbnail(
        string $inputPath,
        string $outputPath,
        int $width,
        int $height,
        bool $crop = true,
        int $quality = 85
    ): bool {
        $this->validateImageFile($inputPath);

        $info = getimagesize($inputPath);
        $mime = $info['mime'];

        $source = $this->createImageFromFile($inputPath, $mime);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($crop) {
            // Calculate crop dimensions to maintain aspect ratio
            $sourceAspect = $sourceWidth / $sourceHeight;
            $thumbAspect = $width / $height;

            if ($sourceAspect > $thumbAspect) {
                // Source is wider
                $cropWidth = (int) ($sourceHeight * $thumbAspect);
                $cropHeight = $sourceHeight;
                $cropX = (int) (($sourceWidth - $cropWidth) / 2);
                $cropY = 0;
            } else {
                // Source is taller
                $cropWidth = $sourceWidth;
                $cropHeight = (int) ($sourceWidth / $thumbAspect);
                $cropX = 0;
                $cropY = (int) (($sourceHeight - $cropHeight) / 2);
            }

            // Create thumbnail with crop
            $thumbnail = imagecreatetruecolor($width, $height);
            imagecopyresampled(
                $thumbnail,
                $source,
                0,
                0,
                $cropX,
                $cropY,
                $width,
                $height,
                $cropWidth,
                $cropHeight
            );
        } else {
            // Resize without crop
            $thumbnail = imagescale($source, $width, $height);
        }

        $result = $this->saveImage($thumbnail, $outputPath, $mime, $quality);

        imagedestroy($source);
        imagedestroy($thumbnail);

        return $result;
    }

    /**
     * Convert image to WebP format.
     *
     * @param  string  $inputPath  Path to input image
     * @param  string  $outputPath  Path to save WebP image
     * @param  int  $quality  Quality (0-100)
     * @return bool Success status
     *
     * @throws Exception If image type is unsupported or file operations fail
     */
    public function convertToWebP(string $inputPath, string $outputPath, int $quality = 80): bool
    {
        $this->validateImageFile($inputPath);

        $info = getimagesize($inputPath);
        $mime = $info['mime'];

        $source = $this->createImageFromFile($inputPath, $mime);
        $result = imagewebp($source, $outputPath, $quality);

        imagedestroy($source);

        return $result;
    }

    /**
     * Convert image to JPEG format.
     *
     * @param  string  $inputPath  Path to input image
     * @param  string  $outputPath  Path to save JPEG image
     * @param  int  $quality  Quality (0-100)
     * @return bool Success status
     *
     * @throws Exception If image type is unsupported or file operations fail
     */
    public function convertToJpeg(string $inputPath, string $outputPath, int $quality = 85): bool
    {
        $this->validateImageFile($inputPath);

        $info = getimagesize($inputPath);
        $mime = $info['mime'];

        $source = $this->createImageFromFile($inputPath, $mime);
        $result = imagejpeg($source, $outputPath, $quality);

        imagedestroy($source);

        return $result;
    }

    /**
     * Add watermark to image.
     *
     * @param  string  $basePath  Path to base image
     * @param  string  $watermarkPath  Path to watermark image (PNG with transparency)
     * @param  string  $outputPath  Path to save watermarked image
     * @param  string  $position  Position: 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'
     * @param  int  $margin  Margin from edges in pixels
     * @param  int  $opacity  Opacity (0-100)
     * @return bool Success status
     *
     * @throws Exception If image type is unsupported or file operations fail
     */
    public function watermark(
        string $basePath,
        string $watermarkPath,
        string $outputPath,
        string $position = 'bottom-right',
        int $margin = 10,
        int $opacity = 100
    ): bool {
        $this->validateImageFile($basePath);
        $this->validateImageFile($watermarkPath);

        $baseInfo = getimagesize($basePath);
        $baseMime = $baseInfo['mime'];

        $base = $this->createImageFromFile($basePath, $baseMime);
        $watermark = $this->createImageFromFile($watermarkPath, getimagesize($watermarkPath)['mime']);

        $baseWidth = imagesx($base);
        $baseHeight = imagesy($base);
        $wmWidth = imagesx($watermark);
        $wmHeight = imagesy($watermark);

        // Calculate position
        [$x, $y] = $this->calculateWatermarkPosition(
            $position,
            $baseWidth,
            $baseHeight,
            $wmWidth,
            $wmHeight,
            $margin
        );

        // Apply opacity if less than 100
        if ($opacity < 100) {
            $alpha = (int) (127 * (100 - $opacity) / 100);
            imagefilter($watermark, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha);
        }

        // Copy watermark to base image
        imagecopy($base, $watermark, $x, $y, 0, 0, $wmWidth, $wmHeight);

        $result = $this->saveImage($base, $outputPath, $baseMime, 85);

        imagedestroy($base);
        imagedestroy($watermark);

        return $result;
    }

    /**
     * Crop image to specified dimensions.
     *
     * @param  string  $inputPath  Path to input image
     * @param  string  $outputPath  Path to save cropped image
     * @param  int  $x  X coordinate of crop area
     * @param  int  $y  Y coordinate of crop area
     * @param  int  $width  Width of crop area
     * @param  int  $height  Height of crop area
     * @param  int  $quality  Quality for JPEG/WebP (0-100)
     * @return bool Success status
     *
     * @throws Exception If image type is unsupported or file operations fail
     */
    public function crop(
        string $inputPath,
        string $outputPath,
        int $x,
        int $y,
        int $width,
        int $height,
        int $quality = 85
    ): bool {
        $this->validateImageFile($inputPath);

        $info = getimagesize($inputPath);
        $mime = $info['mime'];

        $source = $this->createImageFromFile($inputPath, $mime);
        $cropped = imagecrop($source, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);

        if ($cropped === false) {
            throw new Exception('Failed to crop image');
        }

        $result = $this->saveImage($cropped, $outputPath, $mime, $quality);

        imagedestroy($source);
        imagedestroy($cropped);

        return $result;
    }

    /**
     * Get image dimensions.
     *
     * @param  string  $imagePath  Path to image
     * @return array{width: int, height: int, mime: string}
     *
     * @throws Exception If file is not a valid image
     */
    public function getDimensions(string $imagePath): array
    {
        $this->validateImageFile($imagePath);

        $info = getimagesize($imagePath);

        return [
            'width' => $info[0],
            'height' => $info[1],
            'mime' => $info['mime'],
        ];
    }

    /**
     * Validate that file exists and is a supported image type.
     *
     * @throws Exception If file doesn't exist or is not a supported image
     */
    private function validateImageFile(string $path): void
    {
        if (! file_exists($path)) {
            throw new Exception("Image file not found: {$path}");
        }

        $info = @getimagesize($path);

        if ($info === false) {
            throw new Exception("Invalid image file: {$path}");
        }

        if (! in_array($info['mime'], self::SUPPORTED_TYPES)) {
            throw new Exception("Unsupported image type: {$info['mime']}");
        }
    }

    /**
     * Create GD image resource from file based on MIME type.
     *
     * @param  string  $path  Path to image file
     * @param  string  $mime  MIME type of image
     * @return \GdImage|false GD image resource or false on failure
     *
     * @throws Exception If MIME type is unsupported
     */
    private function createImageFromFile(string $path, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new Exception("Unsupported image type: {$mime}")
        };
    }

    /**
     * Save GD image resource to file based on MIME type.
     *
     * @param  resource  $image  GD image resource
     * @param  string  $path  Output path
     * @param  string  $mime  Target MIME type
     * @param  int  $quality  Quality (0-100)
     * @return bool Success status
     *
     * @throws Exception If MIME type is unsupported
     */
    private function saveImage($image, string $path, string $mime, int $quality): bool
    {
        // Ensure directory exists
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, $quality),
            'image/png' => imagepng($image, $path, (int) (9 - ($quality / 11))),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, $quality),
            default => throw new Exception("Unsupported image type: {$mime}")
        };
    }

    /**
     * Calculate watermark position based on position string.
     *
     * @return array{int, int} [x, y] coordinates
     */
    private function calculateWatermarkPosition(
        string $position,
        int $baseWidth,
        int $baseHeight,
        int $wmWidth,
        int $wmHeight,
        int $margin
    ): array {
        return match ($position) {
            'top-left' => [$margin, $margin],
            'top-right' => [$baseWidth - $wmWidth - $margin, $margin],
            'bottom-left' => [$margin, $baseHeight - $wmHeight - $margin],
            'bottom-right' => [$baseWidth - $wmWidth - $margin, $baseHeight - $wmHeight - $margin],
            'center' => [
                (int) (($baseWidth - $wmWidth) / 2),
                (int) (($baseHeight - $wmHeight) / 2),
            ],
            default => [$baseWidth - $wmWidth - $margin, $baseHeight - $wmHeight - $margin]
        };
    }
}
