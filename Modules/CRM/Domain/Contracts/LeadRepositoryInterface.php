<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Contracts;
use Modules\CRM\Domain\Entities\Lead;
interface LeadRepositoryInterface {
    public function findById(int $id, int $tenantId): ?Lead;
    public function save(Lead $lead): Lead;
    public function delete(int $id, int $tenantId): void;
    public function findAll(int $tenantId, int $page, int $perPage): array;
}
