<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

final class ListConfigurationHistoryRequest extends ViewConfigurationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    public function perPage(): int
    {
        return min(max((int) $this->input('per_page', 20), 1), 100);
    }
}
