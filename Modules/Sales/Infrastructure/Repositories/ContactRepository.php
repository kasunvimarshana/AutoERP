<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Repositories;

use Modules\Sales\Domain\Contracts\ContactRepositoryInterface;
use Modules\Sales\Domain\Entities\Contact as ContactEntity;
use Modules\Sales\Domain\Enums\ContactType;
use Modules\Sales\Infrastructure\Models\Contact as ContactModel;

class ContactRepository implements ContactRepositoryInterface
{
    public function findById(int $id): ?ContactEntity
    {
        $m = ContactModel::find($id);

        return $m ? $this->toDomain($m) : null;
    }

    public function findAll(?string $type, int $page = 1, int $perPage = 25): array
    {
        $query = ContactModel::query()->orderBy('name');

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->forPage($page, $perPage)
            ->get()
            ->map(fn (ContactModel $m): ContactEntity => $this->toDomain($m))
            ->all();
    }

    public function save(ContactEntity $contact): ContactEntity
    {
        $data = [
            'tenant_id'       => $contact->getTenantId(),
            'type'            => $contact->getType()->value,
            'name'            => $contact->getName(),
            'email'           => $contact->getEmail(),
            'phone'           => $contact->getPhone(),
            'tax_number'      => $contact->getTaxNumber(),
            'opening_balance' => $contact->getOpeningBalance(),
            'is_active'       => $contact->isActive(),
        ];

        if ($contact->getId() > 0) {
            $m = ContactModel::findOrFail($contact->getId());
            $m->update($data);
        } else {
            $m = ContactModel::create($data);
        }

        return $this->toDomain($m->fresh());
    }

    public function delete(int $id): void
    {
        ContactModel::find($id)?->delete();
    }

    private function toDomain(ContactModel $m): ContactEntity
    {
        return new ContactEntity(
            id: (int) $m->id,
            tenantId: (int) $m->tenant_id,
            type: $m->type instanceof ContactType ? $m->type : ContactType::from((string) $m->type),
            name: (string) $m->name,
            email: $m->email,
            phone: $m->phone,
            taxNumber: $m->tax_number,
            openingBalance: $m->opening_balance !== null ? (string) $m->opening_balance : null,
            isActive: (bool) $m->is_active,
        );
    }
}
