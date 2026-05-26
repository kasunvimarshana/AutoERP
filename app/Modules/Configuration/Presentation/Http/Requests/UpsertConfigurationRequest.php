<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Configuration\Domain\Constants\ConfigurationScope;
use Modules\Configuration\Domain\Constants\ConfigurationSource;

final class UpsertConfigurationRequest extends FormRequest
{
    private const KEY_PATTERN = '/^[a-z0-9._-]+$/i';

    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keyRule = $this->isMethod('post') ? 'required' : 'prohibited';

        return [
            'key' => [$keyRule, 'string', 'max:191', 'regex:' . self::KEY_PATTERN],
            'value' => ['required'],
            'source' => ['nullable', 'string', 'in:' . implode(',', [
                ConfigurationSource::DATABASE,
                ConfigurationSource::ENVIRONMENT,
                ConfigurationSource::RUNTIME,
            ])],
            'description' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'in:' . implode(',', [
                ConfigurationScope::GLOBAL,
                ConfigurationScope::TENANT,
                ConfigurationScope::ORGANIZATION_UNIT,
            ])],
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
