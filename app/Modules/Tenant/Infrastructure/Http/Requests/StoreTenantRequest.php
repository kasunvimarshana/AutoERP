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
            'slug'                        => 'required|string|max:255|unique:tenants,slug',
            'domain'                      => 'nullable|string|max:255|unique:tenants,domain',
            'database_config'             => 'required|array',
            'database_config.driver'      => 'required|string|in:mysql,pgsql,sqlite,sqlsrv',
            'database_config.host'        => 'required|string|max:255',
            'database_config.port'        => 'required|integer|min:1|max:65535',
            'database_config.database'    => 'required|string|max:255',
            'database_config.username'    => 'required|string|max:255',
            'database_config.password'    => 'required|string',
            'mail_config'                 => 'nullable|array',
            'mail_config.driver'          => 'nullable|string|in:smtp,sendmail,mailgun,ses,log,array',
            'mail_config.host'            => 'nullable|string|max:255',
            'mail_config.port'            => 'nullable|integer|min:1|max:65535',
            'cache_config'                => 'nullable|array',
            'cache_config.driver'         => 'nullable|string|in:file,array,database,memcached,redis',
            'queue_config'                => 'nullable|array',
            'queue_config.driver'         => 'nullable|string|in:database,redis,beanstalkd,sqs,fifo,null',
            'feature_flags'               => 'nullable|array',
            'api_keys'                    => 'nullable|array',
            'settings'                    => 'nullable|array',
            'plan'                        => 'required|string|max:100',
            'tenant_plan_id'              => 'nullable|exists:tenant_plans,id',
            'status'                      => 'required|in:active,suspended,pending,cancelled',
            'trial_ends_at'               => 'nullable|date_format:Y-m-d H:i:s',
            'subscription_ends_at'        => 'nullable|date_format:Y-m-d H:i:s',
            'active'                      => 'required|boolean',
            // Optional logo upload
            'logo'                        => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg',
        ];
    }
}
