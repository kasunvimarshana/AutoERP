<?php

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'database_config'          => 'nullable|array',
            'database_config.driver'   => 'nullable|string|in:mysql,pgsql,sqlite',
            'database_config.host'     => 'nullable|string',
            'database_config.port'     => 'nullable|integer',
            'database_config.database' => 'nullable|string',
            'database_config.username' => 'nullable|string',
            'database_config.password' => 'nullable|string',
            'mail_config'              => 'nullable|array',
            'cache_config'             => 'nullable|array',
            'queue_config'             => 'nullable|array',
            'feature_flags'            => 'nullable|array',
            'api_keys'                 => 'nullable|array',
        ];
    }
}
