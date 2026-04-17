<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTenantRequest extends FormRequest
{
    /** @var array<string> */
    private const ALLOWED_INCLUDES = [
        'attachments',
        'tenantPlan',
        'settingsItems',
        'users',
        'roles',
        'permissions',
        'devices',
        'user_attachments',
        'users.roles',
        'users.roles.permissions',
        'users.devices',
        'users.attachments',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'domain' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'status' => 'nullable|in:active,suspended,pending,cancelled',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
            'include' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $includes = array_map('trim', explode(',', $value));
                    foreach ($includes as $include) {
                        if (! in_array($include, self::ALLOWED_INCLUDES, true)) {
                            $fail("The {$attribute} value '{$include}' is not supported.");

                            return;
                        }
                    }
                },
            ],
        ];
    }
}
