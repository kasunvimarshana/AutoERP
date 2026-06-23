<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListCommentRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_unit_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('organization_units')],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('extension.pagination.max_per_page', 200)],
            'commentable_type' => ['nullable', 'string', Rule::in($this->allowedEntityTypes())],
            'commentable_id' => ['nullable', 'integer', 'min:1'],
            'author_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('users')],
        ];
    }

    private function tenantExists(string $table): mixed
    {
        return Rule::exists($table, 'id')->where(
            fn ($query) => $query->where('tenant_id', $this->tenantId()),
        );
    }

    /** @return list<string> */
    private function allowedEntityTypes(): array
    {
        return array_map('strval', array_keys((array) config('extension.entity_types', [])));
    }
}
