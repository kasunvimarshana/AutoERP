<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Traits;

use Illuminate\Http\UploadedFile;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

trait HasImageStorage
{
    protected function storeImage(UploadedFile $image, int $tenantId, string $baseDirectory): string
    {
        /** @var FileStorageServiceInterface $storage */
        $storage = $this->storage;

        return $storage->storeFile($image, "{$baseDirectory}/{$tenantId}");
    }

    protected function deleteImageIfSafe(?string $imagePath, int $tenantId, string $baseDirectory, ?string $excludePath = null): void
    {
        if ($imagePath === null || $imagePath === '' || $imagePath === $excludePath) {
            return;
        }

        $expectedPrefix = "{$baseDirectory}/{$tenantId}/";

        if (! str_starts_with($imagePath, $expectedPrefix)) {
            return;
        }

        /** @var FileStorageServiceInterface $storage */
        $storage = $this->storage;

        if ($storage->exists($imagePath)) {
            $storage->delete($imagePath);
        }
    }
}
