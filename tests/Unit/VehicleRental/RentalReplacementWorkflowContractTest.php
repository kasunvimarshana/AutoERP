<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Illuminate\Support\Facades\Validator;
use Modules\VehicleRental\Http\Requests\StoreRentalReplacementRequest;
use Modules\VehicleRental\Services\RentalReplacementService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

final class RentalReplacementWorkflowContractTest extends TestCase
{
    #[Test]
    public function replacement_request_rejects_the_unimplemented_billing_continuity_choice(): void
    {
        $validator = Validator::make(
            ['billing_continuity_rule' => 'split_period'],
            (new StoreRentalReplacementRequest())->rules(),
        );

        self::assertArrayHasKey(
            'billing_continuity_rule',
            $validator->errors()->toArray(),
        );
    }

    #[Test]
    public function replacement_service_uses_a_named_preserved_period_policy(): void
    {
        $reflection = new ReflectionClass(RentalReplacementService::class);

        self::assertTrue(
            $reflection->hasConstant('PRESERVE_AGREEMENT_BILLING_PERIOD'),
        );
        self::assertSame(
            'continue_period',
            $reflection->getConstant('PRESERVE_AGREEMENT_BILLING_PERIOD'),
        );
    }
}
