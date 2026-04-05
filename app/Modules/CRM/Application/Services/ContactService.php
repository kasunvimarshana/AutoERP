<?php
declare(strict_types=1);
namespace Modules\CRM\Application\Services;

use Modules\CRM\Domain\Entities\Contact;
use Modules\CRM\Domain\Exceptions\ContactNotFoundException;
use Modules\CRM\Domain\RepositoryInterfaces\ContactRepositoryInterface;

class ContactService
{
    public function __construct(private readonly ContactRepositoryInterface $repository) {}

    public function findById(int $id): Contact
    {
        $contact = $this->repository->findById($id);
        if ($contact === null) throw new ContactNotFoundException($id);
        return $contact;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->repository->findAllByTenant($tenantId);
    }

    public function create(array $data): Contact
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Contact
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }

    public function activate(int $id): Contact
    {
        $c = $this->findById($id);
        $c->activate();
        return $this->repository->update($id, ['is_active' => true]) ?? $c;
    }

    public function deactivate(int $id): Contact
    {
        $c = $this->findById($id);
        $c->deactivate();
        return $this->repository->update($id, ['is_active' => false]) ?? $c;
    }
}
