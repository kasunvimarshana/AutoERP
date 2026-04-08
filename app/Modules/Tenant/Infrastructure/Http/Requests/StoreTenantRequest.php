<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                        => 'required|string|max:255',
            'domain'                      => 'nullable|string|unique:tenants,domain',
            'database_config'             => 'required|array',
            'database_config.driver'      => 'required|string|in:mysql,pgsql,sqlite',
            'database_config.host'        => 'required|string',
            'database_config.port'        => 'required|integer',
            'database_config.database'    => 'required|string',
            'database_config.username'    => 'required|string',
            'database_config.password'    => 'required|string',
            'mail_config'                 => 'nullable|array',
            'cache_config'                => 'nullable|array',
            'queue_config'                => 'nullable|array',
            'feature_flags'               => 'nullable|array',
            'api_keys'                    => 'nullable|array',
            'active'                      => 'boolean',
            // Optional logo upload
            'logo'                        => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg',
        ];
    }
}
