<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notification\Enums\NotificationType;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $channel = $this->route('channel');

        return $this->user()->can('update', $channel);
    }

    public function rules(): array
    {
        $channel = $this->route('channel');

        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('notification_channels', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($channel->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::enum(NotificationType::class)],
            'driver' => ['sometimes', 'required', 'string', 'max:100'],
            'configuration' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
        ];
    }
}
