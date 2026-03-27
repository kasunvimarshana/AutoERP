<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Modules\Brand\Application\Contracts\DeleteBrandLogoServiceInterface;
use Modules\Brand\Domain\Exceptions\BrandLogoNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;

class DeleteBrandLogoService extends BaseService implements DeleteBrandLogoServiceInterface
{
    public function __construct(
        protected BrandLogoRepositoryInterface $logoRepository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($logoRepository);
    }

    protected function handle(array $data): bool
    {
        $logoId = $data['logo_id'];
        $logo = $this->logoRepository->find($logoId);

        if (! $logo) {
            throw new BrandLogoNotFoundException($logoId);
        }

        $this->storage->delete($logo->getFilePath());

        return $this->logoRepository->delete($logoId);
    }
}
