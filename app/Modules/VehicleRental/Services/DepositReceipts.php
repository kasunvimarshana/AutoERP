<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentSourceService;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\CustomerAgreement;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class DepositReceipts
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly UserAccessResolver $access,
        private readonly PaymentCreationService $payments,
        private readonly PaymentSourceService $sources,
        private readonly DecimalMath $math,
    ) {}

    public function summary(AgreementContext $context, int $agreementId): array
    {
        $this->authorize($context, PaymentPermission::PAYMENTS_VIEW);
        $agreement = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($agreementId);
        $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, PaymentSourceType::RentalAgreementDeposit, $agreementId);
        $net = $this->sources->netReceipts($documents);
        $required = $agreement->terms['deposit_requirement'] ?? null;

        return ['requirement' => $required, 'net_receipts' => $net,
            'remaining_to_receive' => $required === null ? null : $this->math->sub($required, $net), 'payments' => $documents];
    }

    public function receive(AgreementContext $context, int $agreementId, int $expectedVersion, string $key, array $input, array $lines): Payment
    {
        $this->authorize($context, PaymentPermission::PAYMENTS_CREATE);

        return DB::transaction(function () use ($context, $agreementId, $expectedVersion, $key, $input, $lines): Payment {
            $agreement = CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->lockForUpdate()->findOrFail($agreementId);
            if ($agreement->row_version !== $expectedVersion) {
                throw new ConflictHttpException('The agreement changed. Reload it before receiving a deposit.');
            }
            $required = $agreement->terms['deposit_requirement'] ?? null;
            if ($agreement->status !== AgreementStatus::Active || $required === null || $this->math->compare($required, AgreementFields::ZERO) <= 0) {
                throw ValidationException::withMessages(['agreement' => ['An active agreement with an explicit positive deposit requirement is needed.']]);
            }
            if (trim($key) === '') {
                throw ValidationException::withMessages(['idempotency_key' => ['Provide a stable receipt request key.']]);
            }
            $payment = $this->payments->create(new CreatePaymentData(
                tenantId: $context->tenantId, organizationUnitId: $context->organizationUnitId,
                paymentType: PaymentType::Advance, direction: PaymentDirection::Inbound, paymentDate: $input['payment_date'],
                partyType: AgreementKind::Customer->value, partyId: (int) $agreement->customer_id, currencyId: $agreement->currency_id,
                sourceType: PaymentSourceType::RentalAgreementDeposit->value, sourceId: $agreement->id,
                exchangeRate: $input['exchange_rate'], referenceNumber: $input['reference_number'] ?? null,
                notes: $input['notes'] ?? null, createdBy: $context->actorId, lines: $lines, idempotencyKey: $key,
                metadata: ['agreement_reference' => $agreement->reference, 'agreement_version' => $agreement->row_version, 'deposit_requirement' => $required],
            ));
            // Check after idempotent creation so an exact retry at the limit still succeeds.
            $documents = $this->sources->documents($context->tenantId, $context->organizationUnitId, PaymentSourceType::RentalAgreementDeposit, $agreementId);
            if ($this->math->compare($this->sources->netReceipts($documents), $required) > 0) {
                throw new ConflictHttpException('This receipt exceeds the remaining agreed deposit. Review existing payments, including drafts.');
            }

            return $payment;
        });
    }

    private function authorize(AgreementContext $context, string $permission): void
    {
        $this->authorization->assert($context, AgreementKind::Customer, false);
        if (! $this->access->can($context->actorId, $context->tenantId, $permission)) {
            throw new AuthorizationException('This deposit action requires permission: '.$permission);
        }
    }
}
