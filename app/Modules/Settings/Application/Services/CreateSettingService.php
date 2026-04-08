<?php

declare(strict_types=1);

namespace Modules\Settings\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Settings\Application\Contracts\CreateSettingServiceInterface;
use Modules\Settings\Application\DTOs\SettingData;
use Modules\Settings\Domain\Entities\Setting;
use Modules\Settings\Domain\Events\SettingCreated;
use Modules\Settings\Domain\RepositoryInterfaces\SettingRepositoryInterface;

class CreateSettingService extends BaseService implements CreateSettingServiceInterface
{
    public function __construct(private readonly SettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): Setting
    {
        $dto = SettingData::fromArray($data);

        $setting = new Setting(
            tenantId:     $dto->tenantId,
            groupKey:     $dto->groupKey,
            settingKey:   $dto->settingKey,
            label:        $dto->label,
            value:        $dto->value,
            defaultValue: $dto->defaultValue,
            settingType:  $dto->settingType,
            description:  $dto->description,
            isSystem:     $dto->isSystem,
            isEditable:   $dto->isEditable,
            metadata:     $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new SettingCreated($saved));

        return $saved;
    }
}
