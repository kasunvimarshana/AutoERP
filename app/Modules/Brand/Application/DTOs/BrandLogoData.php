<?php

declare(strict_types=1);

namespace Modules\Brand\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class BrandLogoData extends BaseDto
{
    public int $brand_id;

    public ?array $file;

    public ?array $metadata;

    public function rules(): array
    {
        return [
            'brand_id' => 'required|integer|exists:brands,id',
            'file'     => 'required|array',
            'metadata' => 'nullable|array',
        ];
    }
}
