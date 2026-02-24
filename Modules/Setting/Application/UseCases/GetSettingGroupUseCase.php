<?php
namespace Modules\Setting\Application\UseCases;
use Modules\Setting\Application\Services\SettingService;
class GetSettingGroupUseCase
{
    public function __construct(private SettingService $service) {}
    public function execute(array $data): array
    {
        return $this->service->getGroup($data['group'], $data['tenant_id']);
    }
}
