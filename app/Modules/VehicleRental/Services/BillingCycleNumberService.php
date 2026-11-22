<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\VehicleRental\Data\AgreementContext;
use RuntimeException;

final class BillingCycleNumberService
{
    private const DOCUMENT_TYPE = 'vehicle_rental_billing_cycle';

    private const PREFIX = 'VRBC-{PERIOD}-';

    private const PERIOD_TYPE = 'yearly';

    private const PADDING = 5;

    private const MAX_GENERATION_ATTEMPTS = 5;

    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(AgreementContext $context, ?string $atDate = null): string
    {
        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $result = $this->sequences->execute([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => null,
                'document_type' => self::DOCUMENT_TYPE,
                'period_type' => self::PERIOD_TYPE,
                'at_date' => $atDate ?: now()->toDateString(),
                'prefix' => self::PREFIX,
                'padding' => self::PADDING,
            ]);

            if ($result->isFailure()) {
                throw new RuntimeException($result->errorOrFail()->message);
            }

            $candidate = (string) $result->valueOrFail()['generated_number'];

            $exists = DB::table('vehicle_rental_billing_cycles')
                ->where('tenant_id', $context->tenantId)
                ->where('cycle_number', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        throw new RuntimeException('Failed to generate a unique billing cycle number after multiple attempts.');
    }
}
