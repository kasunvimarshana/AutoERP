<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

use Modules\Configuration\Services\ConfigurationAuthorizationService;
use Modules\Core\Http\Requests\TenantScopedRequest;

class ViewConfigurationRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        $scope = $this->route('scope');

        return is_string($scope)
            && app(ConfigurationAuthorizationService::class)->canViewScopeCurrent($scope);
    }

    public function rules(): array
    {
        return [];
    }
}
