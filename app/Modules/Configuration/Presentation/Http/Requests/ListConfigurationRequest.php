<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Constants\ConfigurationScope;
use Modules\Configuration\Domain\Constants\ConfigurationSource;

final class ListConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prefix' => ['nullable', 'string', 'max:191'],
            'source' => ['nullable', 'string', 'in:' . implode(',', [
                ConfigurationSource::DATABASE,
                ConfigurationSource::ENVIRONMENT,
                ConfigurationSource::RUNTIME,
            ])],
            'scope' => ['nullable', 'string', 'in:' . implode(',', [
                ConfigurationScope::GLOBAL,
                ConfigurationScope::TENANT,
                ConfigurationScope::ORGANIZATION_UNIT,
            ])],
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . ConfigurationDefaults::MAX_PER_PAGE],
        ];
    }
}
