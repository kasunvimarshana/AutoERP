<?php
namespace Modules\Setting\Application\UseCases;
use Modules\Setting\Application\Services\SettingService;
class UpdateSettingUseCase
{
    public function __construct(private SettingService $service) {}
    public function execute(array $data): void
    {
        $this->service->set(
            $data['key'],
            $data['value'],
            $data['tenant_id'],
            $data['group'],
            $data['type'] ?? 'string'
        );
    }
}
