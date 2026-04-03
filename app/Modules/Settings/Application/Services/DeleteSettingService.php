<?php

declare(strict_types=1);

namespace Modules\Settings\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Settings\Application\Contracts\DeleteSettingServiceInterface;
use Modules\Settings\Domain\Entities\Setting;
use Modules\Settings\Domain\Events\SettingDeleted;
use Modules\Settings\Domain\Exceptions\SettingNotFoundException;
use Modules\Settings\Domain\RepositoryInterfaces\SettingRepositoryInterface;

class DeleteSettingService extends BaseService implements DeleteSettingServiceInterface
{
    public function __construct(private readonly SettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];

        /** @var Setting|null $setting */
        $setting = $this->settingRepository->find($id);
        if (! $setting) {
            throw new SettingNotFoundException($id);
        }

        $this->settingRepository->delete($id);
        $this->addEvent(new SettingDeleted($setting));

        return true;
    }
}
