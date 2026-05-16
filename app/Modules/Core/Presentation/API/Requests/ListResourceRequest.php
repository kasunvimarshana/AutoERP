<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Application\DTOs\ListResourcesDto;

class ListResourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'filters' => ['sometimes', 'array'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string', 'max:100'],
            'include' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function toDto(): ListResourcesDto
    {
        $validated = $this->validated();

        return new ListResourcesDto(
            filters: is_array($validated['filters'] ?? null) ? $validated['filters'] : [],
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
            include: $validated['include'] ?? null,
        );
    }
}
