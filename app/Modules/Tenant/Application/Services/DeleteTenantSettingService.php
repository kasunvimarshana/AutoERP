<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\DeleteTenantSettingServiceInterface;
use Modules\Tenant\Domain\Events\TenantSettingDeleted;
use Modules\Tenant\Domain\Exceptions\TenantSettingNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;

class DeleteTenantSettingService extends BaseService implements DeleteTenantSettingServiceInterface
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository
    ) {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): bool
    {
        $tenantId = (int) $data['tenant_id'];
        $key = (string) $data['key'];

        $existing = $this->settingRepository->findByTenantAndKey($tenantId, $key);
        if (! $existing || $existing->getId() === null) {
            throw new TenantSettingNotFoundException("{$tenantId}:{$key}");
        }

        $deleted = $this->settingRepository->delete($existing->getId());
        if ($deleted) {
            $this->addEvent(new TenantSettingDeleted($existing->getTenantId(), $existing->getId()));
        }

        return $deleted;
    }
}
