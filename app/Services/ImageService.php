<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Resizes and re-encodes uploads to WebP so stored images are small and stripped of EXIF data.
 */
class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * @return array{path: string, thumb_path: string}
     */
    public function storeProfilePhoto(UploadedFile $file, string $directory): array
    {
        $name = Str::uuid()->toString();

        $image = $this->manager->read($file->getRealPath())->orient()->scaleDown(1200, 1200);
        $path = "{$directory}/{$name}.webp";
        Storage::disk('public')->put($path, (string) $image->toWebp(80));

        $thumb = $this->manager->read($file->getRealPath())->orient()->cover(400, 500);
        $thumbPath = "{$directory}/thumbs/{$name}.webp";
        Storage::disk('public')->put($thumbPath, (string) $thumb->toWebp(75));

        return ['path' => $path, 'thumb_path' => $thumbPath];
    }

    /** Page illustrations keep their aspect ratio (portrait posters included). */
    public function storePageImage(UploadedFile $file): string
    {
        $path = 'pages/'.Str::uuid().'.webp';
        $image = $this->manager->read($file->getRealPath())->scaleDown(1400, 2100);
        Storage::disk('public')->put($path, (string) $image->toWebp(82));

        return $path;
    }

    public function storeBanner(UploadedFile $file): string
    {
        $path = 'banners/'.Str::uuid().'.webp';
        $image = $this->manager->read($file->getRealPath())->scaleDown(1920, 1080);
        Storage::disk('public')->put($path, (string) $image->toWebp(82));

        return $path;
    }
}
