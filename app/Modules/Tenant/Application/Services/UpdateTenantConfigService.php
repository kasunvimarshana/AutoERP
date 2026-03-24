<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\DTOs\TenantConfigData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantConfigChanged;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UpdateTenantConfigService extends BaseService implements UpdateTenantConfigServiceInterface
{
    private TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->tenantRepository = $repository;
    }

    protected function handle(array $data): Tenant
    {
        $id = $data['id'];
        $dto = TenantConfigData::fromArray($data);

        $tenant = $this->tenantRepository->find($id);
        if (! $tenant) {
            throw new TenantNotFoundException($id);
        }

        $tenant->updateConfig($dto->toArray());
        $saved = $this->tenantRepository->save($tenant);
        $this->addEvent(new TenantConfigChanged($saved));

        return $saved;
    }
}
