<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Inventory\Models\InventoryMovement;
use Modules\VehicleService\Constants\VehicleServiceFinanceSource;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceInventoryFinanceService
{
    private const ZERO = '0.000000';

    private const BASE_EXCHANGE_RATE = '1.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $postings,
        private readonly FinanceSourceReversalInterface $reversals,
    ) {}

    public function reverseIssue(VehicleServiceJob $job, InventoryMovement $movement, int $actorId, string $reason): void
    {
        // Zero-cost issues deliberately have no finance posting (see postIssue).
        if ($this->math->isZero((string) $movement->total_cost)) {
            return;
        }

        $this->reversals->reverseSource(
            (int) $job->tenant_id,
            $job->organization_unit_id,
            VehicleServiceFinanceSource::MODULE,
            VehicleServiceFinanceSource::INVENTORY_ISSUE,
            (int) $movement->getKey(),
            now()->toDateString(),
            $actorId,
            $reason,
        );
    }

    public function postIssue(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        InventoryMovement $movement,
        ?int $actorId = null,
    ): ?PostingResultData {
        $amount = $this->math->normalize((string) $movement->total_cost);
        if ($this->math->isZero($amount)) {
            return null;
        }

        $postingDate = $movement->movement_date->toDateString();
        $description = 'Inventory issued to vehicle service job '.$job->job_number;

        return $this->postings->post(new PostingContext(
            source: new PostingSourceData(
                sourceType: VehicleServiceFinanceSource::INVENTORY_ISSUE,
                sourceId: (int) $movement->getKey(),
                tenantId: (int) $job->tenant_id,
                organizationUnitId: $job->organization_unit_id,
                sourceModule: VehicleServiceFinanceSource::MODULE,
                sourceNumber: (string) $job->job_number,
                sourceDate: $postingDate,
            ),
            postingDate: $postingDate,
            exchangeRate: self::BASE_EXCHANGE_RATE,
            lines: [
                new PostingLine(
                    lineName: 'Vehicle service parts consumed',
                    debit: $amount,
                    credit: self::ZERO,
                    description: $description,
                    profileKey: FinanceAccountRoleCode::CostOfGoodsSold->value,
                    sourceLineType: VehicleServiceFinanceSource::JOB_LINE,
                    sourceLineId: (int) $line->getKey(),
                ),
                new PostingLine(
                    lineName: 'Inventory issued',
                    debit: self::ZERO,
                    credit: $amount,
                    description: $description,
                    profileKey: FinanceAccountRoleCode::Inventory->value,
                    sourceLineType: VehicleServiceFinanceSource::JOB_LINE,
                    sourceLineId: (int) $line->getKey(),
                ),
            ],
            description: $description,
            postingProfileCode: FinancePostingProfileCode::InventoryIssue->value,
        ), $actorId);
    }
}
