<?php

declare(strict_types=1);

namespace Tests\Unit\Voucher;

use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\TenantEntitlementReaderInterface;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Finance\Constants\FinancePermission;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Voucher\DTOs\VoucherAccessScope;
use Modules\Voucher\Enums\VoucherSourceKind;
use Modules\Voucher\Enums\VoucherSourceModule;
use Modules\Voucher\Enums\VoucherType;
use Modules\Voucher\Services\VoucherAccessPolicy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class VoucherAccessPolicyTest extends TestCase
{
    public function test_scope_requires_both_enabled_source_module_and_view_permission(): void
    {
        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $entitlements = $this->createMock(TenantEntitlementReaderInterface::class);
        $entitlements->method('featureEnabled')->willReturnMap([
            [11, TenantFeature::PAYMENT, true],
            [11, TenantFeature::FINANCE, false],
        ]);
        $permissions->method('allows')->willReturnMap([
            [7, 11, PaymentPermission::PAYMENTS_VIEW, true],
            [7, 11, FinancePermission::JOURNALS_VIEW, true],
        ]);

        $scope = new VoucherAccessPolicy($permissions, $entitlements)->scopeFor(7, 11);

        self::assertTrue($scope->payments);
        self::assertFalse($scope->finance);
    }

    public function test_list_and_detail_are_constrained_to_authorized_source(): void
    {
        $policy = $this->policy();
        $paymentOnly = new VoucherAccessScope(payments: true, finance: false);

        self::assertSame(
            VoucherSourceModule::Payment->value,
            $policy->constrainListFilters([], $paymentOnly)['source_module'],
        );
        self::assertSame(
            VoucherSourceKind::Payment->value,
            $policy->authorizedSourceKind(VoucherType::Receipt, null, $paymentOnly),
        );
        self::assertSame(
            VoucherSourceKind::PaymentReversal->value,
            $policy->authorizedSourceKind(VoucherType::Reversal, null, $paymentOnly),
        );
    }

    public function test_unauthorized_source_requests_fail_closed(): void
    {
        $policy = $this->policy();

        $this->expectException(AccessDeniedHttpException::class);
        $policy->constrainListFilters(
            ['source_module' => VoucherSourceModule::Finance->value],
            new VoucherAccessScope(payments: true, finance: false),
        );
    }

    private function policy(): VoucherAccessPolicy
    {
        return new VoucherAccessPolicy(
            $this->createStub(PermissionCheckerInterface::class),
            $this->createStub(TenantEntitlementReaderInterface::class),
        );
    }
}
