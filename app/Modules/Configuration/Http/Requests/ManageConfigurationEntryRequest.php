<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Http\Requests;

use Modules\Configuration\Services\ConfigurationAuthorizationService;

abstract class ManageConfigurationEntryRequest extends ViewConfigurationRequest
{
    public function authorize(): bool
    {
        $scope = $this->route('scope');

        return is_string($scope)
            && app(ConfigurationAuthorizationService::class)->canManageScopeCurrent($scope);
    }
}
