<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Entities;

use DateTimeImmutable;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;

class ReturnRefund
{
    public function __construct(
        public readonly ?int             $id,
        public readonly int              $tenantId,
        public readonly ?int             $orgUnitId,
        public readonly int              $rentalId,
        public readonly string           $returnNumber,
        public readonly ReturnStatus     $status,
        public readonly DateTimeImmutable $returnedAt,
        public readonly ?string          $endOdometer,
        public readonly ?string          $actualDays,
        public readonly string           $rentalCharge,
        public readonly string           $extraCharges,
        public readonly string           $damageCharges,
        public readonly string           $fuelCharges,
        public readonly string           $depositPaid,
        public readonly string           $refundAmount,
        public readonly ?string          $refundMethod,
        public readonly ?string          $inspectionNotes,
        public readonly ?string          $notes,
        public readonly ?array           $damagePhotos,
        public readonly ?array           $metadata,
        public readonly bool             $isActive,
        public readonly int              $rowVersion,
    ) {}
}
