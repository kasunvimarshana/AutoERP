<?php

declare(strict_types=1);

namespace Modules\CRM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ContactData extends BaseDto
{
    public ?string $id = null;
    public string $contactableType = '';
    public string $contactableId = '';
    public string $firstName = '';
    public ?string $lastName = null;
    public ?string $title = null;
    public ?string $department = null;
    public ?string $position = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $mobile = null;
    public bool $isPrimary = false;
    public ?string $notes = null;

    /**
     * Validation rules for creating/updating a contact.
     */
    public function rules(): array
    {
        return [
            'contactable_type' => ['required', 'string'],
            'contactable_id'   => ['required', 'string'],
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['nullable', 'string', 'max:100'],
            'email'            => ['nullable', 'email', 'max:200'],
        ];
    }
}
