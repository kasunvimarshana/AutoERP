<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\VehicleRental\Data\AgreementContext;
use RuntimeException;

final class RunningChartNumberService
{
    private const DOCUMENT_TYPE = 'vehicle_rental_running_chart';

    private const PREFIX = 'RC-{PERIOD}-';

    private const PERIOD_TYPE = 'yearly';

    private const PADDING = 5;

    private const MAX_GENERATION_ATTEMPTS = 5;

    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(AgreementContext $context, ?string $usageDate = null): string
    {
        $atDate = $usageDate !== null && trim($usageDate) !== ''
            ? substr($usageDate, 0, 10)
            : now()->toDateString();

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $result = $this->sequences->execute([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => null,
                'document_type' => self::DOCUMENT_TYPE,
                'period_type' => self::PERIOD_TYPE,
                'at_date' => $atDate,
                'prefix' => self::PREFIX,
                'padding' => self::PADDING,
            ]);

            if ($result->isFailure()) {
                throw new RuntimeException($result->errorOrFail()->message);
            }

            $candidate = (string) $result->valueOrFail()['generated_number'];

            $exists = DB::table('vehicle_rental_running_charts')
                ->where('tenant_id', $context->tenantId)
                ->where('reference', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to allocate a unique running chart reference after multiple attempts.');
    }
}
