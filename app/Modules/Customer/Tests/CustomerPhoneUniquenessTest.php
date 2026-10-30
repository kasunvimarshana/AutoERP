<?php

declare(strict_types=1);

namespace Modules\Customer\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\DTOs\CustomerContactData;
use Modules\Customer\DTOs\UpdateCustomerData;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Exceptions\CustomerPhoneConflict;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerContactService;
use Modules\Customer\Services\CustomerCreationService;
use Modules\Customer\Services\CustomerPhoneService;
use Modules\Customer\Services\CustomerUpdateService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class CustomerPhoneUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private int $reference = 0;

    public function test_creation_requires_a_number_and_updates_cannot_remove_the_last_one(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            try {
                app(CustomerCreationService::class)->create(new CreateCustomerData(
                    tenantId: $tenant, code: 'NO-CONTACT', name: 'No contact', customerType: CustomerType::Individual,
                ));
                self::fail('Customer was created without a phone or WhatsApp number.');
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('phone', $exception->errors());
                self::assertArrayHasKey('mobile', $exception->errors());
            }

            $legacy = $this->create($tenant, mobile: '0776580914');
            DB::table('customers')->where('id', $legacy->id)->update(['phone' => null, 'mobile' => null]);
            $legacy = $legacy->fresh();
            $renamed = app(CustomerUpdateService::class)->update($legacy, new UpdateCustomerData(
                rowVersion: (int) $legacy->row_version, name: 'Legacy customer renamed', provided: ['name'],
            ));
            self::assertSame('Legacy customer renamed', $renamed->name);
            try {
                app(CustomerUpdateService::class)->update($renamed, new UpdateCustomerData(
                    rowVersion: (int) $renamed->row_version, phone: null, mobile: null, provided: ['phone', 'mobile'],
                ));
                self::fail('Customer number removal was accepted.');
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('phone', $exception->errors());
            }
            $repaired = app(CustomerUpdateService::class)->update($renamed, new UpdateCustomerData(
                rowVersion: (int) $renamed->row_version, phone: '0112345678', mobile: null, provided: ['phone', 'mobile'],
            ));
            self::assertSame('0112345678', $repaired->phone);
        });
    }

    public function test_formats_and_cross_field_duplicates_are_blocked_but_names_and_own_numbers_can_repeat(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $owner = $this->create($tenant, mobile: '0776580914');
            foreach (['077 658 0914', '+94 (77) 658-0914', '0094776580914', '94776580914'] as $number) {
                $this->conflict(fn () => $this->create($tenant, phone: $number), $owner, 'phone');
            }
            $sameName = $this->create($tenant, phone: '0112345678', mobile: '0112345678');
            self::assertSame($owner->name, $sameName->name);
            $contact = app(CustomerContactService::class)->create($sameName, new CustomerContactData('Owner', mobile: '+94 11 234 5678'));
            self::assertSame(1, $contact->row_version);
            self::assertSame(2, DB::table('customers')->count());
        });
    }

    public function test_customer_and_contact_updates_check_all_owners_and_stale_contact_writes_are_rejected(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $owner = $this->create($tenant, mobile: '0776580914');
            $other = $this->create($tenant, phone: '0112345678');
            $contacts = app(CustomerContactService::class);
            $this->conflict(fn () => $contacts->create($other, new CustomerContactData('Other', phone: '+94776580914')), $owner, 'phone');
            $contact = $contacts->create($owner, new CustomerContactData('Owner', mobile: '0712345678'));
            $this->conflict(fn () => $this->create($tenant, phone: '94712345678'), $owner, 'phone');
            $this->conflict(fn () => app(CustomerUpdateService::class)->update($other, new UpdateCustomerData(
                rowVersion: (int) $other->row_version, mobile: '0712345678', provided: ['mobile'],
            )), $owner, 'mobile');
            $this->conflict(fn () => $contacts->update($owner, $contact, new CustomerContactData('Owner', phone: '0112345678', rowVersion: 1)), $other, 'phone');
            $updated = $contacts->update($owner, $contact, new CustomerContactData('Owner edited', mobile: '0712345678', rowVersion: 1));
            self::assertSame(2, $updated->row_version);
            foreach (['update', 'delete'] as $action) {
                try {
                    $action === 'update'
                        ? $contacts->update($owner, $contact, new CustomerContactData('Stale', rowVersion: 1))
                        : $contacts->delete($owner, $contact, 1);
                    self::fail('A stale contact write was accepted.');
                } catch (ConflictHttpException) {
                    self::assertSame('Owner edited', $updated->fresh()->contact_name);
                }
            }
            $contacts->delete($owner, $updated, 2);
            self::assertNotNull($this->create($tenant, mobile: '0712345678')->id);
        });
    }

    public function test_related_customer_creation_rolls_back_on_a_contact_conflict_and_invalid_numbers_are_rejected(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $owner = $this->create($tenant, mobile: '0776580914');
            $this->conflict(fn () => app(CustomerCreationService::class)->create(new CreateCustomerData(
                tenantId: $tenant, code: 'REL', name: 'Related', customerType: CustomerType::Individual,
                mobile: '0700000001',
                contacts: [new CustomerContactData('Contact', phone: '94776580914')],
            )), $owner, 'contacts.0.phone');
            self::assertSame(1, DB::table('customers')->count());
            self::assertSame(0, DB::table('customer_contacts')->count());
            try {
                $this->create($tenant, mobile: 'not-a-number');
                self::fail('Invalid number was accepted.');
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('mobile', $exception->errors());
            }
        });
    }

    public function test_tenant_isolation_and_archived_customer_identity_are_preserved(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $owner = $this->create($tenant, mobile: '0776580914');
            $owner->delete();
            $this->conflict(fn () => $this->create($tenant, phone: '0776580914'), $owner, 'phone');
        });
        $this->withTenantExecutionContext($otherTenant, function () use ($otherTenant, $tenant): void {
            self::assertNotNull($this->create($otherTenant, phone: '0776580914')->id);
            $this->expectException(AuthorizationException::class);
            app(CustomerPhoneService::class)->write($tenant, fn () => null);
        });
    }

    public function test_audit_lists_existing_duplicates_and_invalid_numbers_without_changing_records(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $first = $this->create($tenant, mobile: '0776580914');
            $second = $this->create($tenant, mobile: '0712345678');
            $missing = $this->create($tenant, mobile: '0700000000');
            DB::table('customers')->where('id', $second->id)->update(['phone' => '+94 77 658 0914', 'mobile' => 'invalid']);
            DB::table('customers')->where('id', $missing->id)->update(['phone' => null, 'mobile' => null]);
            $before = DB::table('customers')->orderBy('id')->get()->toJson();
            $path = tempnam(sys_get_temp_dir(), 'customer-phone-audit-');
            try {
                $this->artisan('customers:audit-phone-numbers', ['tenant' => DB::table('tenants')->where('id', $tenant)->value('code'), '--output' => $path])
                    ->assertSuccessful();
                $csv = file_get_contents($path);
                self::assertStringContainsString('Shared by customers', $csv);
                self::assertStringContainsString('Invalid number', $csv);
                self::assertStringContainsString('Missing number', $csv);
                self::assertStringContainsString($missing->code, $csv);
                self::assertStringContainsString($first->code, $csv);
                self::assertStringContainsString('94776580914', $csv);
            } finally {
                unlink($path);
            }
            self::assertSame($before, DB::table('customers')->orderBy('id')->get()->toJson());
        });
    }

    private function conflict(callable $write, Customer $owner, string $field): void
    {
        try {
            $write();
            self::fail('Duplicate customer number was accepted.');
        } catch (CustomerPhoneConflict $exception) {
            self::assertSame($field, $exception->field);
            self::assertSame((int) $owner->id, $exception->customer['id']);
            self::assertSame('CUSTOMER_PHONE_IN_USE', $exception->render()->getData(true)['error']['code']);
        }
    }

    private function create(int $tenant, ?string $phone = null, ?string $mobile = null): Customer
    {
        $this->reference++;

        return app(CustomerCreationService::class)->create(new CreateCustomerData(
            tenantId: $tenant, code: 'PHONE-'.$this->reference, name: 'Same customer name',
            customerType: CustomerType::Individual, phone: $phone, mobile: $mobile,
        ));
    }

    private function tenant(): int
    {
        $suffix = Str::random(10);

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => 'PHONE-'.$suffix, 'name' => 'Phone test',
            'slug' => 'phone-'.strtolower($suffix), 'status' => 'active', 'status_changed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
