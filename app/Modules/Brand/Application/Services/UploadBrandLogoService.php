<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Illuminate\Support\Str;
use Modules\Brand\Application\Contracts\UploadBrandLogoServiceInterface;
use Modules\Brand\Domain\Entities\BrandLogo;
use Modules\Brand\Domain\Exceptions\BrandNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;

class UploadBrandLogoService extends BaseService implements UploadBrandLogoServiceInterface
{
    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
        protected BrandLogoRepositoryInterface $logoRepository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($brandRepository);
    }

    protected function handle(array $data): BrandLogo
    {
        $brandId = $data['brand_id'];
        $fileInfo = $data['file'];
        $metadata = $data['metadata'] ?? null;

        $brand = $this->brandRepository->find($brandId);
        if (! $brand) {
            throw new BrandNotFoundException($brandId);
        }

        // Remove existing logo if one exists
        $this->logoRepository->deleteByBrand($brandId);

        $uuid = (string) Str::uuid();
        $path = $this->storage->store($fileInfo['tmp_path'], "brands/{$brandId}", $fileInfo['name']);

        $logo = new BrandLogo(
            tenantId: $brand->getTenantId(),
            brandId: $brandId,
            uuid: $uuid,
            name: $fileInfo['name'],
            filePath: $path,
            mimeType: $fileInfo['mime_type'],
            size: $fileInfo['size'],
            metadata: is_array($metadata) ? $metadata : null,
        );

        return $this->logoRepository->save($logo);
    }
}
