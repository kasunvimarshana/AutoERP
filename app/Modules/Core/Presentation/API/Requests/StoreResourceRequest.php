<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Application\DTOs\CreateResourceDto;

class StoreResourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array', 'min:1'],
        ];
    }

    public function toDto(): CreateResourceDto
    {
        $validated = $this->validated();

        return new CreateResourceDto(
            attributes: is_array($validated['attributes'] ?? null) ? $validated['attributes'] : []
        );
    }
}
