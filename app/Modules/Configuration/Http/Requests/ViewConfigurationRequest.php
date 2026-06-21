<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

use Modules\Configuration\Services\ConfigurationAuthorizationService;
use Modules\Core\Http\Requests\TenantScopedRequest;

class ViewConfigurationRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return parent::authorize()
            && app(ConfigurationAuthorizationService::class)->canViewCurrent();
    }

    public function rules(): array
    {
        return [];
    }
}
