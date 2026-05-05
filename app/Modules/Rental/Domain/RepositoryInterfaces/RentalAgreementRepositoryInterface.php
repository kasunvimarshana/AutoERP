<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalAgreement;

interface RentalAgreementRepositoryInterface
{
    public function create(RentalAgreement $agreement): void;
    public function findById(string $id): ?RentalAgreement;
    public function findByAgreementNumber(string $tenantId, string $agreementNumber): ?RentalAgreement;
    public function findByReservationId(string $reservationId): ?RentalAgreement;
    public function getActive(string $tenantId, int $page = 1, int $limit = 50): array;
    public function update(RentalAgreement $agreement): void;
}
