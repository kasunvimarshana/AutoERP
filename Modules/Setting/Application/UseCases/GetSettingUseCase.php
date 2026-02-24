<?php
namespace Modules\Setting\Application\UseCases;
use Modules\Setting\Application\Services\SettingService;
class GetSettingUseCase
{
    public function __construct(private SettingService $service) {}
    public function execute(array $data): mixed
    {
        return $this->service->get($data['key'], $data['tenant_id'], $data['default'] ?? null);
    }
}
