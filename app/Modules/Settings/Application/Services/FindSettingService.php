<?php

declare(strict_types=1);

namespace Modules\Settings\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Settings\Application\Contracts\FindSettingServiceInterface;
use Modules\Settings\Domain\RepositoryInterfaces\SettingRepositoryInterface;

class FindSettingService extends BaseService implements FindSettingServiceInterface
{
    public function __construct(private readonly SettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->settingRepository->find($data['id'] ?? null);
    }
}
