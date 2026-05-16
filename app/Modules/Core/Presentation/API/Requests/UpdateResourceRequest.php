<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Application\DTOs\UpdateResourceDto;

class UpdateResourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array', 'min:1'],
        ];
    }

    public function toDto(int|string $id): UpdateResourceDto
    {
        $validated = $this->validated();

        return new UpdateResourceDto(
            id: $id,
            attributes: is_array($validated['attributes'] ?? null) ? $validated['attributes'] : []
        );
    }
}
