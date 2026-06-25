<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;

final class ListPlatformAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'cursor' => ['nullable', 'string', 'max:500'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.max(1, (int) config('audit.pagination.max_per_page', 100))],
            'event_category' => ['nullable', 'string', Rule::in(AuditEventCategory::values())],
            'event_name' => ['nullable', 'string', 'max:150'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'actor_type' => ['nullable', 'string', Rule::in(AuditActorType::values())],
            'actor_id' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'string', 'max:150'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ];
    }
}
