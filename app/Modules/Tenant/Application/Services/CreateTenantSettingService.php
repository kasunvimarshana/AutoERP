<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\CreateTenantSettingServiceInterface;
use Modules\Tenant\Application\DTOs\TenantSettingData;
use Modules\Tenant\Domain\Entities\TenantSetting;
use Modules\Tenant\Domain\Events\TenantSettingCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;

class CreateTenantSettingService extends BaseService implements CreateTenantSettingServiceInterface
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository
    ) {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): TenantSetting
    {
        $dto = TenantSettingData::fromArray($data);

        $setting = new TenantSetting(
            tenantId: $dto->tenant_id,
            key: $dto->key,
            value: $dto->value,
            group: $dto->group,
            isPublic: $dto->is_public
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new TenantSettingCreated($saved));

        return $saved;
    }
}
