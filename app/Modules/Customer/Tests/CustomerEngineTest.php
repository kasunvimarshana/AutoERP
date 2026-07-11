<?php

declare(strict_types=1);

namespace Modules\Customer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\DTOs\CustomerCreditProfileData;
use Modules\Customer\DTOs\UpdateCustomerData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerCreationService;
use Modules\Customer\Services\CustomerCreditProfileService;
use Modules\Customer\Services\CustomerLookupService;
use Modules\Customer\Services\CustomerUpdateService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class CustomerEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_creation_persists_identity_and_one_authoritative_credit_profile(): void
    {
        $tenantId = $this->tenant();

        $customer = $this->withTenantExecutionContext($tenantId, fn (): Customer => app(CustomerCreationService::class)->create(
            $this->createData($tenantId, new CustomerCreditProfileData(
                creditLimit: '25000.000000',
                creditPeriodDays: 30,
                warningThresholdPercent: '75.000000',
                creditAllowed: true,
                advanceAllowed: false,
                allowOverCredit: false,
                allowPartialPayment: true,
            )),
        ));

        self::assertSame('CUS-TEST', $customer->code);
        self::assertFalse(array_key_exists('credit_limit', $customer->getAttributes()));
        self::assertFalse(array_key_exists('opening_balance', $customer->getAttributes()));
        self::assertFalse(array_key_exists('is_credit_allowed', $customer->getAttributes()));
        self::assertFalse(array_key_exists('is_advance_allowed', $customer->getAttributes()));

        $profile = $this->withTenantExecutionContext(
            $tenantId,
            fn () => $customer->creditProfile()->firstOrFail(),
        );
        self::assertSame(1, (int) $profile->row_version);
        self::assertSame('25000.000000', (string) $profile->credit_limit);
        self::assertSame(30, (int) $profile->credit_period_days);
        self::assertTrue((bool) $profile->credit_allowed);
        self::assertFalse((bool) $profile->advance_allowed);
    }

    public function test_customer_master_update_does_not_mutate_credit_policy(): void
    {
        $tenantId = $this->tenant();
        $customer = $this->customer($tenantId);
        $profileBefore = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => $customer->creditProfile()->firstOrFail()->getAttributes(),
        );

        $updated = $this->withTenantExecutionContext($tenantId, fn (): Customer => app(CustomerUpdateService::class)->update(
            $customer,
            new UpdateCustomerData(
                rowVersion: (int) $customer->row_version,
                name: 'Updated Customer Name',
                provided: ['name'],
            ),
        ));
        $profileAfter = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => $updated->creditProfile()->firstOrFail()->getAttributes(),
        );

        self::assertSame('Updated Customer Name', $updated->name);
        self::assertSame(2, (int) $updated->row_version);
        self::assertSame($profileBefore, $profileAfter);
    }

    public function test_credit_profile_update_requires_exact_profile_version(): void
    {
        $tenantId = $this->tenant();
        $customer = $this->customer($tenantId);
        $profile = $this->withTenantExecutionContext(
            $tenantId,
            fn () => $customer->creditProfile()->firstOrFail(),
        );

        $updated = $this->withTenantExecutionContext($tenantId, fn () => app(CustomerCreditProfileService::class)->set(
            $customer,
            new CustomerCreditProfileData(
                creditLimit: '50000.000000',
                creditPeriodDays: 45,
                warningThresholdPercent: '80.000000',
                creditAllowed: true,
                advanceAllowed: true,
                allowOverCredit: true,
                allowPartialPayment: false,
                isActive: true,
                rowVersion: (int) $profile->row_version,
            ),
        ));

        self::assertSame(2, (int) $updated->row_version);
        self::assertSame('50000.000000', (string) $updated->credit_limit);
        self::assertTrue((bool) $updated->allow_over_credit);
        self::assertFalse((bool) $updated->allow_partial_payment);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Customer credit profile was changed by someone else.');

        $this->withTenantExecutionContext($tenantId, fn () => app(CustomerCreditProfileService::class)->set(
            $customer,
            new CustomerCreditProfileData(
                creditLimit: '60000.000000',
                rowVersion: (int) $profile->row_version,
            ),
        ));
    }

    public function test_credit_lookup_uses_active_profile_policy(): void
    {
        $tenantId = $this->tenant();
        $allowed = $this->customer($tenantId, 'CUS-ALLOWED', true);
        $blocked = $this->customer($tenantId, 'CUS-BLOCKED', false);

        $customers = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(CustomerLookupService::class)->customersAllowedForCredit($tenantId),
        );

        self::assertTrue($customers->contains('id', $allowed->getKey()));
        self::assertFalse($customers->contains('id', $blocked->getKey()));
    }

    public function test_customer_result_reads_credit_and_advance_flags_from_profile(): void
    {
        $tenantId = $this->tenant();
        $customer = $this->customer($tenantId, 'CUS-RESULT', true, false);

        $result = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(CustomerLookupService::class)->result($customer->load('creditProfile')),
        );

        self::assertSame('10000.000000', $result->creditLimit);
        self::assertTrue($result->creditAllowed);
        self::assertFalse($result->advanceAllowed);
    }

    private function customer(
        int $tenantId,
        string $code = 'CUS-TEST',
        bool $creditAllowed = true,
        bool $advanceAllowed = true,
    ): Customer {
        return $this->withTenantExecutionContext($tenantId, fn (): Customer => app(CustomerCreationService::class)->create(
            $this->createData($tenantId, new CustomerCreditProfileData(
                creditLimit: '10000.000000',
                creditAllowed: $creditAllowed,
                advanceAllowed: $advanceAllowed,
            ), $code),
        ));
    }

    private function createData(
        int $tenantId,
        CustomerCreditProfileData $creditProfile,
        string $code = 'CUS-TEST',
    ): CreateCustomerData {
        return new CreateCustomerData(
            tenantId: $tenantId,
            customerNumber: 'NUM-'.$code,
            code: $code,
            name: 'Customer '.$code,
            customerType: CustomerType::Company,
            status: CustomerStatus::Active,
            creditProfile: $creditProfile,
        );
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CUS-'.$suffix,
            'name' => 'Customer Tenant '.$suffix,
            'slug' => 'customer-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
