<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\ReferenceData\Services\ReferenceDataAuthorizationService;

final class ListReferenceDataRequest extends TenantScopedRequest
{
    private const MAX_PER_PAGE = 1000;

    public function authorize(): bool
    {
        return parent::authorize()
            && app(ReferenceDataAuthorizationService::class)->canViewCurrent();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    public function perPage(): int
    {
        $value = $this->input('per_page', 25);
        $perPage = is_numeric($value) ? (int) $value : 25;

        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }
}
