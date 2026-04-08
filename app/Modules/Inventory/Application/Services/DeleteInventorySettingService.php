<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventorySettingServiceInterface;
use Modules\Inventory\Domain\Events\InventorySettingDeleted;
use Modules\Inventory\Domain\Exceptions\InventorySettingNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;

class DeleteInventorySettingService extends BaseService implements DeleteInventorySettingServiceInterface
{
    public function __construct(private readonly InventorySettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): bool
    {
        $id      = $data['id'];
        $setting = $this->settingRepository->find($id);

        if (! $setting) {
            throw new InventorySettingNotFoundException($id);
        }

        $this->addEvent(new InventorySettingDeleted($setting));

        return $this->settingRepository->delete($id);
    }
}
