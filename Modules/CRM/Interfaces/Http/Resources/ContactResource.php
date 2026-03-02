<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Crm\Domain\Entities\Contact;

class ContactResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Contact $contact */
        $contact = $this->resource;

        return [
            'id' => $contact->id,
            'tenant_id' => $contact->tenantId,
            'first_name' => $contact->firstName,
            'last_name' => $contact->lastName,
            'full_name' => $contact->fullName(),
            'email' => $contact->email,
            'phone' => $contact->phone,
            'company' => $contact->company,
            'job_title' => $contact->jobTitle,
            'status' => $contact->status->value,
            'notes' => $contact->notes,
            'created_at' => $contact->createdAt,
            'updated_at' => $contact->updatedAt,
        ];
    }
}
