<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateProfileData extends BaseDto
{
    public string $first_name;

    public string $last_name;

    public ?string $phone;

    public ?array $address;

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|array',
        ];
    }
}
