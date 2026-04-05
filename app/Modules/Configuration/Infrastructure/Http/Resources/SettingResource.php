<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Domain\Entities\Setting;

class SettingResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Setting $setting */
        $setting = $this->resource;
        return [
            'id' => $setting->getId(),
            'tenant_id' => $setting->getTenantId(),
            'key' => $setting->getKey(),
            'value' => $setting->getValue(),
            'type' => $setting->getType(),
            'description' => $setting->getDescription(),
            'created_at' => $setting->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $setting->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
