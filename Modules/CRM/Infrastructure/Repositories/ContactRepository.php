<?php

declare(strict_types=1);

namespace Modules\Crm\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Entities\Contact;
use Modules\Crm\Domain\Enums\ContactStatus;
use Modules\Crm\Infrastructure\Models\ContactModel;

class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    protected function model(): string
    {
        return ContactModel::class;
    }

    public function findById(int $id, int $tenantId): ?Contact
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (ContactModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Contact $contact): Contact
    {
        if ($contact->id !== null) {
            $model = $this->newQuery()
                ->where('id', $contact->id)
                ->where('tenant_id', $contact->tenantId)
                ->firstOrFail();
        } else {
            $model = new ContactModel;
            $model->tenant_id = $contact->tenantId;
        }

        $model->first_name = $contact->firstName;
        $model->last_name = $contact->lastName;
        $model->email = $contact->email;
        $model->phone = $contact->phone;
        $model->company = $contact->company;
        $model->job_title = $contact->jobTitle;
        $model->status = $contact->status->value;
        $model->notes = $contact->notes;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Contact with ID {$id} not found.");
        }

        $model->delete();
    }

    private function toDomain(ContactModel $model): Contact
    {
        return new Contact(
            id: $model->id,
            tenantId: $model->tenant_id,
            firstName: $model->first_name,
            lastName: $model->last_name,
            email: $model->email,
            phone: $model->phone,
            company: $model->company,
            jobTitle: $model->job_title,
            status: ContactStatus::from($model->status),
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
