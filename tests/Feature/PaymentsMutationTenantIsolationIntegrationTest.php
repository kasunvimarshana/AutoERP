<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\Application\Contracts\PaymentServiceInterface;
use Modules\Payments\Application\DTOs\CreatePaymentDTO;
use Modules\Payments\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payments\Domain\ValueObjects\PaymentMethod;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Tests\TestCase;

class PaymentsMutationTenantIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testGetByIdRejectsCrossTenantAccess(): void
    {
        $service = app(PaymentServiceInterface::class);

        $tenantA = '11111111-1111-1111-1111-111111111111';
        $tenantB = '22222222-2222-2222-2222-222222222222';

        $created = $service->create($this->makeCreateDto(
            tenantId: $tenantA,
            orgUnitId: $tenantA,
            paymentNumber: 'PAY-ISO-READ-001',
        ));

        try {
            $service->getById($tenantB, $created->id);
            $this->fail('Expected cross-tenant payment read to be rejected.');
        } catch (PaymentNotFoundException) {
            $this->assertDatabaseHas('fleet_payments', [
                'id' => $created->id,
                'tenant_id' => $tenantA,
                'payment_number' => 'PAY-ISO-READ-001',
                'status' => 'pending',
            ]);
        }
    }

    public function testUpdateStatusRejectsCrossTenantMutation(): void
    {
        $service = app(PaymentServiceInterface::class);

        $tenantA = '33333333-3333-3333-3333-333333333333';
        $tenantB = '44444444-4444-4444-4444-444444444444';

        $created = $service->create($this->makeCreateDto(
            tenantId: $tenantA,
            orgUnitId: $tenantA,
            paymentNumber: 'PAY-ISO-STATUS-001',
        ));

        try {
            $service->updateStatus($tenantB, $created->id, PaymentStatus::Completed->value);
            $this->fail('Expected cross-tenant payment status mutation to be rejected.');
        } catch (PaymentNotFoundException) {
            $this->assertDatabaseHas('fleet_payments', [
                'id' => $created->id,
                'tenant_id' => $tenantA,
                'payment_number' => 'PAY-ISO-STATUS-001',
                'status' => 'pending',
            ]);
        }
    }

    public function testDeleteRejectsCrossTenantMutation(): void
    {
        $service = app(PaymentServiceInterface::class);

        $tenantA = '55555555-5555-5555-5555-555555555555';
        $tenantB = '66666666-6666-6666-6666-666666666666';

        $created = $service->create($this->makeCreateDto(
            tenantId: $tenantA,
            orgUnitId: $tenantA,
            paymentNumber: 'PAY-ISO-DELETE-001',
        ));

        try {
            $service->delete($tenantB, $created->id);
            $this->fail('Expected cross-tenant payment delete mutation to be rejected.');
        } catch (PaymentNotFoundException) {
            $this->assertDatabaseHas('fleet_payments', [
                'id' => $created->id,
                'tenant_id' => $tenantA,
                'payment_number' => 'PAY-ISO-DELETE-001',
                'status' => 'pending',
            ]);
        }
    }

    private function makeCreateDto(string $tenantId, string $orgUnitId, string $paymentNumber): CreatePaymentDTO
    {
        return new CreatePaymentDTO(
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            paymentNumber: $paymentNumber,
            invoiceId: '99999999-9999-9999-9999-999999999999',
            paymentMethod: PaymentMethod::Card,
            amount: '500.000000',
            currency: 'USD',
            referenceNumber: 'ISO-REF',
            notes: 'Payments tenant mutation isolation',
            metadata: ['test' => true],
        );
    }
}
