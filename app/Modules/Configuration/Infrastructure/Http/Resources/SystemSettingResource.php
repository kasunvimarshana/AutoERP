<?php
namespace Modules\Configuration\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Domain\Entities\SystemSetting;

class SystemSettingResource extends JsonResource
{
    public function __construct(private readonly SystemSetting $setting) { parent::__construct($setting); }
    public function toArray($request): array {
        return [
            'id' => $this->setting->id,
            'tenant_id' => $this->setting->tenantId,
            'group' => $this->setting->group,
            'key' => $this->setting->key,
            'value' => $this->setting->isEncrypted ? '***' : $this->setting->value,
            'type' => $this->setting->type,
            'is_encrypted' => $this->setting->isEncrypted,
            'is_public' => $this->setting->isPublic,
        ];
    }
}
