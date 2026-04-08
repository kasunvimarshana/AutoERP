<?php

declare(strict_types=1);

namespace Modules\Settings\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Settings\Application\Contracts\UpdateSettingServiceInterface;
use Modules\Settings\Application\DTOs\UpdateSettingData;
use Modules\Settings\Domain\Entities\Setting;
use Modules\Settings\Domain\Events\SettingUpdated;
use Modules\Settings\Domain\Exceptions\SettingNotFoundException;
use Modules\Settings\Domain\RepositoryInterfaces\SettingRepositoryInterface;

class UpdateSettingService extends BaseService implements UpdateSettingServiceInterface
{
    public function __construct(private readonly SettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): Setting
    {
        $dto = UpdateSettingData::fromArray($data);

        /** @var Setting|null $setting */
        $setting = $this->settingRepository->find($dto->id);
        if (! $setting) {
            throw new SettingNotFoundException($dto->id);
        }

        $setting->updateDetails(
            groupKey:     $dto->groupKey     ?? $setting->getGroupKey(),
            settingKey:   $dto->settingKey   ?? $setting->getSettingKey(),
            label:        $dto->label        ?? $setting->getLabel(),
            value:        $dto->value        ?? $setting->getRawValue(),
            defaultValue: $dto->defaultValue ?? $setting->getDefaultValue(),
            settingType:  $dto->settingType  ?? $setting->getSettingType(),
            description:  $dto->description  ?? $setting->getDescription(),
            isSystem:     $dto->isSystem     ?? $setting->isSystem(),
            isEditable:   $dto->isEditable   ?? $setting->isEditable(),
            metadata:     $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new SettingUpdated($saved));

        return $saved;
    }
}
