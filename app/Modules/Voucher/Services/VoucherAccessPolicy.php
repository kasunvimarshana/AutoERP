<?php

declare(strict_types=1);

namespace Modules\Voucher\Services;

use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Finance\Constants\FinancePermission;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Tenant\Services\TenantEntitlementService;
use Modules\Voucher\DTOs\VoucherAccessScope;
use Modules\Voucher\Enums\VoucherSourceKind;
use Modules\Voucher\Enums\VoucherType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class VoucherAccessPolicy
{
    public function __construct(
        private readonly PermissionCheckerInterface $permissions,
        private readonly TenantEntitlementService $entitlements,
    ) {}

    public function scopeFor(int $userId, int $tenantId): VoucherAccessScope
    {
        return new VoucherAccessScope(
            payments: $this->entitlements->featureEnabled($tenantId, TenantFeature::PAYMENT)
                && $this->permissions->allows($userId, $tenantId, PaymentPermission::PAYMENTS_VIEW),
            finance: $this->entitlements->featureEnabled($tenantId, TenantFeature::FINANCE)
                && $this->permissions->allows($userId, $tenantId, FinancePermission::JOURNALS_VIEW),
        );
    }

    /**
     * @param array<string, array<string, mixed>> $definitions
     * @return list<array<string, mixed>>
     */
    public function visibleTypeDefinitions(array $definitions, VoucherAccessScope $scope): array
    {
        $this->assertAny($scope);

        return array_values(array_filter(
            $definitions,
            static function (array $definition) use ($scope): bool {
                $kind = (string) ($definition['source_kind'] ?? '');

                if ($kind === VoucherSourceKind::Payment->value) {
                    return $scope->payments;
                }
                if ($kind === VoucherSourceKind::FinanceJournal->value) {
                    return $scope->finance;
                }

                return $scope->any();
            },
        ));
    }

    public function authorizedSourceKind(
        VoucherType $type,
        ?string $requestedSourceKind,
        VoucherAccessScope $scope,
    ): ?string {
        $this->assertAny($scope);

        return match ($type) {
            VoucherType::Receipt, VoucherType::Payment => $this->paymentSource($scope),
            VoucherType::Journal,
            VoucherType::Contra,
            VoucherType::Adjustment,
            VoucherType::OpeningBalance => $this->financeSource($scope),
            VoucherType::Reversal => $this->reversalSource($requestedSourceKind, $scope),
        };
    }

    public function assertAny(VoucherAccessScope $scope): void
    {
        if (! $scope->any()) {
            throw new AccessDeniedHttpException('You do not have permission to view vouchers.');
        }
    }

    private function paymentSource(VoucherAccessScope $scope): string
    {
        if (! $scope->payments) {
            throw new AccessDeniedHttpException('You do not have permission to view payment vouchers.');
        }

        return VoucherSourceKind::Payment->value;
    }

    private function financeSource(VoucherAccessScope $scope): string
    {
        if (! $scope->finance) {
            throw new AccessDeniedHttpException('You do not have permission to view Finance vouchers.');
        }

        return VoucherSourceKind::FinanceJournal->value;
    }

    private function reversalSource(?string $requestedSourceKind, VoucherAccessScope $scope): ?string
    {
        $requested = $requestedSourceKind === null
            ? null
            : VoucherSourceKind::tryFrom($requestedSourceKind);

        if ($requested?->isPaymentSource() === true) {
            if (! $scope->payments) {
                throw new AccessDeniedHttpException('You do not have permission to view payment reversals.');
            }

            return VoucherSourceKind::PaymentReversal->value;
        }

        if ($requested === VoucherSourceKind::FinanceJournal) {
            return $this->financeSource($scope);
        }

        if ($scope->payments && ! $scope->finance) {
            return VoucherSourceKind::PaymentReversal->value;
        }
        if ($scope->finance && ! $scope->payments) {
            return VoucherSourceKind::FinanceJournal->value;
        }

        return null;
    }
}
