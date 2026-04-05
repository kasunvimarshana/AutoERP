<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\RepositoryInterfaces;
use Modules\CRM\Domain\Entities\Contact;
interface ContactRepositoryInterface {
    public function findById(int $id): ?Contact;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): Contact;
    public function update(int $id, array $data): ?Contact;
    public function delete(int $id): bool;
}
