<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Index Audit Log Request
 *
 * Validation for audit log filtering and pagination
 */
class IndexAuditLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', \Modules\Audit\Models\AuditLog::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            // Filtering
            'event' => ['sometimes', 'string', 'max:255'],
            'auditable_type' => ['sometimes', 'string', 'max:255'],
            'auditable_id' => ['sometimes', 'uuid'],
            'user_id' => ['sometimes', 'uuid'],
            'organization_id' => ['sometimes', 'uuid'],
            'ip_address' => ['sometimes', 'ip'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date', 'after_or_equal:from_date'],
            'search' => ['sometimes', 'string', 'max:255'],

            // Pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            // Sorting
            'sort_by' => ['sometimes', 'string', 'in:created_at,event,auditable_type,user_id'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Get validated filter parameters
     */
    public function getFilters(): array
    {
        return $this->only([
            'event',
            'auditable_type',
            'auditable_id',
            'user_id',
            'organization_id',
            'ip_address',
            'from_date',
            'to_date',
            'search',
        ]);
    }

    /**
     * Get pagination parameters
     */
    public function getPagination(): array
    {
        return [
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
        ];
    }

    /**
     * Get sorting parameters
     */
    public function getSorting(): array
    {
        return [
            'sort_by' => $this->input('sort_by', 'created_at'),
            'sort_order' => $this->input('sort_order', 'desc'),
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'to_date.after_or_equal' => 'The end date must be equal to or after the start date.',
            'per_page.max' => 'Maximum items per page is 100.',
            'event.max' => 'Event name is too long.',
            'auditable_type.max' => 'Auditable type name is too long.',
        ];
    }
}
