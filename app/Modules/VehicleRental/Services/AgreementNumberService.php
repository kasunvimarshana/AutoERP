<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use RuntimeException;

final class AgreementNumberService
{
    private const CUSTOMER_DOCUMENT_TYPE = 'vehicle_rental_customer_agreement';

    private const OWNER_DOCUMENT_TYPE = 'vehicle_rental_owner_agreement';

    private const CUSTOMER_PREFIX = 'VRC-{PERIOD}-';

    private const OWNER_PREFIX = 'VRO-{PERIOD}-';

    private const PERIOD_TYPE = 'yearly';

    private const PADDING = 5;

    private const MAX_GENERATION_ATTEMPTS = 5;

    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(AgreementKind $kind, AgreementContext $context, ?string $agreedOn = null): string
    {
        $table = $kind === AgreementKind::Customer
            ? 'vehicle_rental_customer_agreements'
            : 'vehicle_rental_owner_agreements';
        $docType = $kind === AgreementKind::Customer
            ? self::CUSTOMER_DOCUMENT_TYPE
            : self::OWNER_DOCUMENT_TYPE;
        $prefix = $kind === AgreementKind::Customer
            ? self::CUSTOMER_PREFIX
            : self::OWNER_PREFIX;

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $result = $this->sequences->execute([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => null,
                'document_type' => $docType,
                'period_type' => self::PERIOD_TYPE,
                'at_date' => $agreedOn ?: now()->toDateString(),
                'prefix' => $prefix,
                'padding' => self::PADDING,
            ]);

            if ($result->isFailure()) {
                throw new RuntimeException($result->errorOrFail()->message);
            }

            $candidate = (string) $result->valueOrFail()['generated_number'];

            $exists = DB::table($table)
                ->where('tenant_id', $context->tenantId)
                ->where('reference', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to generate a unique agreement reference.');
    }
}
