<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListUserRequest extends FormRequest
{
    /** @var array<string> */
    private const ALLOWED_INCLUDES = ['roles', 'permissions', 'attachments', 'devices'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'org_unit_id' => 'nullable|integer|min:1',
            'email' => 'nullable|email',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,suspended',
            'active' => 'nullable|boolean',
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
