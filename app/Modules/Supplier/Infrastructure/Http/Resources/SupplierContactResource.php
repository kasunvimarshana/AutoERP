<?php
namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Domain\Entities\SupplierContact;

class SupplierContactResource extends JsonResource
{
    public function __construct(private readonly SupplierContact $contact)
    {
        parent::__construct($contact);
    }

    public function toArray($request): array
    {
        return [
            'id'          => $this->contact->id,
            'supplier_id' => $this->contact->supplierId,
            'name'        => $this->contact->name,
            'email'       => $this->contact->email,
            'phone'       => $this->contact->phone,
            'position'    => $this->contact->position,
            'is_primary'  => $this->contact->isPrimary,
        ];
    }
}
