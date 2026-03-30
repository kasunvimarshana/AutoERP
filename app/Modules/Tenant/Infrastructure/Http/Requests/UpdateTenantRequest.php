<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'name'                        => 'sometimes|required|string|max:255',
            'domain'                      => 'nullable|string|unique:tenants,domain,'.$tenantId,
            'database_config'             => 'sometimes|required|array',
            'database_config.driver'      => 'sometimes|required|string|in:mysql,pgsql,sqlite',
            'database_config.host'        => 'sometimes|required|string',
            'database_config.port'        => 'sometimes|required|integer',
            'database_config.database'    => 'sometimes|required|string',
            'database_config.username'    => 'sometimes|required|string',
            'database_config.password'    => 'sometimes|required|string',
            'mail_config'                 => 'nullable|array',
            'cache_config'                => 'nullable|array',
            'queue_config'                => 'nullable|array',
            'feature_flags'               => 'nullable|array',
            'api_keys'                    => 'nullable|array',
            'active'                      => 'boolean',
            // Optional logo replacement
            'logo'                        => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg',
        ];
    }
}
