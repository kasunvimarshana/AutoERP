<?php declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalAgreement;

interface ManageRentalAgreementServiceInterface
{
    public function create(array $data): RentalAgreement;

    public function find(int $tenantId, string $id): RentalAgreement;

    public function getActive(int $tenantId): array;
}
