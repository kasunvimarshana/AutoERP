<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\DeleteProductUomSettingServiceInterface;
use Modules\UoM\Domain\Events\ProductUomSettingDeleted;
use Modules\UoM\Domain\Exceptions\ProductUomSettingNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;

class DeleteProductUomSettingService extends BaseService implements DeleteProductUomSettingServiceInterface
{
    private ProductUomSettingRepositoryInterface $settingRepository;

    public function __construct(ProductUomSettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->settingRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id      = $data['id'];
        $setting = $this->settingRepository->find($id);

        if (! $setting) {
            throw new ProductUomSettingNotFoundException($id);
        }

        $tenantId = $setting->getTenantId();
        $deleted  = $this->settingRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new ProductUomSettingDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
