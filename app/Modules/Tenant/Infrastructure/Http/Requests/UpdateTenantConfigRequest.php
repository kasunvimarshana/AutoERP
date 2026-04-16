<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTenantConfigRequest extends FormRequest
{
    private const CONFIG_FIELDS = [
        'database_config',
        'mail_config',
        'cache_config',
        'queue_config',
        'feature_flags',
        'api_keys',
        'settings',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'database_config' => 'sometimes|array',
            'database_config.driver' => 'required_with:database_config|string|in:mysql,pgsql,sqlite,sqlsrv',
            'database_config.host' => 'required_with:database_config|string|max:255',
            'database_config.port' => 'required_with:database_config|integer|min:1|max:65535',
            'database_config.database' => 'required_with:database_config|string|max:255',
            'database_config.username' => 'required_with:database_config|string|max:255',
            'database_config.password' => 'required_with:database_config|string',
            'mail_config' => 'sometimes|nullable|array',
            'mail_config.driver' => 'required_with:mail_config|string|in:smtp,sendmail,mailgun,ses,log,array',
            'mail_config.host' => 'required_with:mail_config|string|max:255',
            'mail_config.port' => 'required_with:mail_config|integer|min:1|max:65535',
            'mail_config.username' => 'required_with:mail_config|string|max:255',
            'mail_config.password' => 'required_with:mail_config|string',
            'mail_config.from' => 'required_with:mail_config|email|max:255',
            'cache_config' => 'sometimes|nullable|array',
            'cache_config.driver' => 'required_with:cache_config|string|in:file,array,database,memcached,redis',
            'queue_config' => 'sometimes|nullable|array',
            'queue_config.driver' => 'required_with:queue_config|string|in:database,redis,beanstalkd,sqs,fifo,null',
            'feature_flags' => 'sometimes|array',
            'api_keys' => 'sometimes|array',
            'settings' => 'sometimes|nullable|array',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $input = $this->all();

            foreach (self::CONFIG_FIELDS as $field) {
                if (array_key_exists($field, $input)) {
                    return;
                }
            }

            $validator->errors()->add(
                'config',
                'At least one configuration field must be provided.'
            );
        });
    }
}
