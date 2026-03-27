<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Modules\Brand\Application\Contracts\DeleteBrandServiceInterface;
use Modules\Brand\Domain\Events\BrandDeleted;
use Modules\Brand\Domain\Exceptions\BrandNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class DeleteBrandService extends BaseService implements DeleteBrandServiceInterface
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepository)
    {
        parent::__construct($brandRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $brand = $this->brandRepository->find($id);

        if (! $brand) {
            throw new BrandNotFoundException($id);
        }

        $tenantId = $brand->getTenantId();
        $deleted = $this->brandRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new BrandDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
