<?php

declare(strict_types=1);

namespace Modules\VehicleService\Contracts;

use Modules\Invoice\Models\Invoice;
use Modules\VehicleService\Models\VehicleServiceJob;

interface JobPromotionHandlerInterface
{
    public const REDEEM_PERMISSION = 'promotion.redeem';

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function choose(
        int $tenantId,
        ?int $organizationUnitId,
        int $actorId,
        int $jobId,
        int $lineId,
        int $expectedVersion,
        array $input
    ): array;

    public function hasJobChoices(VehicleServiceJob $job): bool;

    public function clearLineChoice(int $tenantId, int $lineId): void;

    /**
     * @param array<int, string> $lineQuantities
     */
    public function assertInvoiceChoices(VehicleServiceJob $job, array $lineQuantities): void;

    public function recordPostedInvoice(VehicleServiceJob $job, Invoice $invoice, ?int $actorId): void;

    public function reverseInvoice(int $tenantId, int $invoiceId, ?int $actorId): void;
}
