<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Services\ConfigurationAuthorizationService;
use Modules\Core\Http\Requests\TenantScopedRequest;

class ViewConfigurationRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $scope = $this->route('scope');
        $authorization = app(ConfigurationAuthorizationService::class);

        return $scope === ConfigurationScope::GLOBAL
            ? $authorization->canViewPlatformDefaultsCurrent()
            : $authorization->canViewCurrent();
    }

    public function rules(): array
    {
        return [];
    }
}
