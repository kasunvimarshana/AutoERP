<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Services\PhoneNumberNormalizer;
use Modules\Customer\Exceptions\CustomerPhoneConflict;

class CustomerPhoneService
{
    private const TransactionAttempts = 3;

    private const RequiredNumberMessage = 'Add a phone or WhatsApp number for this customer.';

    public function __construct(private readonly PhoneNumberNormalizer $numbers, private readonly TenantExecutionContextInterface $context) {}

    /**
     * Serialize customer/contact identity writes across branches of a tenant.
     * The lock is acquired before customer, contact, or sequence locks.
     */
    public function write(int $tenantId, Closure $write): mixed
    {
        $this->assertTenant($tenantId);

        return DB::transaction(function () use ($tenantId, $write): mixed {
            if (DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->first() === null) {
                throw ValidationException::withMessages(['tenant_id' => 'The business could not be found.']);
            }

            return $write();
        }, self::TransactionAttempts);
    }

    /** @param array<string, string|null> $fields */
    public function assertAvailable(int $tenantId, ?int $organizationUnitId, ?int $customerId, array $fields): void
    {
        $wanted = [];
        foreach ($fields as $field => $value) {
            if ($value === null || trim($value) === '') {
                continue;
            }
            try {
                $wanted[$field] = $this->numbers->normalize($value);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([$field => $exception->getMessage()]);
            }
        }
        if ($wanted === []) {
            return;
        }

        foreach ($this->entries($tenantId, lock: true) as $entry) {
            if ($entry['customer_id'] === $customerId) {
                continue;
            }
            foreach ($wanted as $field => $number) {
                if ($number === $entry['number']) {
                    $visible = $entry['organization_unit_id'] === null || $entry['organization_unit_id'] === $organizationUnitId;
                    throw new CustomerPhoneConflict($field, $visible ? [
                        'id' => $entry['customer_id'],
                        'name' => $entry['name'],
                        'code' => $entry['code'],
                        'archived' => $entry['archived'],
                    ] : null);
                }
            }
        }
    }

    public function assertCustomerNumber(?string $phone, ?string $mobile): void
    {
        if (trim((string) $phone) !== '' || trim((string) $mobile) !== '') {
            return;
        }

        throw ValidationException::withMessages([
            'phone' => self::RequiredNumberMessage,
            'mobile' => self::RequiredNumberMessage,
        ]);
    }

    /** @return iterable<array<string, mixed>> */
    public function entries(int $tenantId, bool $lock = false): iterable
    {
        $this->assertTenant($tenantId);
        // Include archived customers so deleting a customer does not bypass identity checks.
        $customers = DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->select('id as customer_id', 'organization_unit_id', 'name', 'code', 'phone', 'mobile', 'deleted_at');
        $contacts = DB::table('customer_contacts as contacts')
            ->join('customers', function ($join): void {
                $join->on('customers.id', '=', 'contacts.customer_id')
                    ->on('customers.tenant_id', '=', 'contacts.tenant_id');
            })
            ->where('contacts.tenant_id', $tenantId)
            ->whereNull('contacts.deleted_at')
            ->select('customers.id as customer_id', 'customers.organization_unit_id', 'customers.name', 'customers.code', 'contacts.phone', 'contacts.mobile', 'customers.deleted_at');

        foreach (['Customer' => $customers, 'Contact' => $contacts] as $source => $query) {
            if ($lock) {
                $query->lockForUpdate();
            }
            foreach ($query->cursor() as $row) {
                if ($source === 'Customer' && trim((string) $row->phone) === '' && trim((string) $row->mobile) === '') {
                    yield [
                        'customer_id' => (int) $row->customer_id,
                        'organization_unit_id' => $row->organization_unit_id === null ? null : (int) $row->organization_unit_id,
                        'name' => $row->name,
                        'code' => $row->code,
                        'archived' => $row->deleted_at !== null,
                        'number' => null,
                        'original' => '',
                        'field' => 'Customer phone or WhatsApp',
                        'missing' => true,
                    ];

                    continue;
                }
                foreach (['phone', 'mobile'] as $field) {
                    if ($row->{$field} === null || trim($row->{$field}) === '') {
                        continue;
                    }
                    try {
                        $number = $this->numbers->normalize($row->{$field});
                    } catch (InvalidArgumentException) {
                        $number = null;
                    }
                    yield [
                        'customer_id' => (int) $row->customer_id,
                        'organization_unit_id' => $row->organization_unit_id === null ? null : (int) $row->organization_unit_id,
                        'name' => $row->name,
                        'code' => $row->code,
                        'archived' => $row->deleted_at !== null,
                        'number' => $number,
                        'original' => $row->{$field},
                        'field' => $source.' '.($field === 'mobile' ? 'WhatsApp/mobile' : 'phone'),
                    ];
                }
            }
        }
    }

    private function assertTenant(int $tenantId): void
    {
        if ($this->context->tenantId() !== $tenantId) {
            throw new AuthorizationException('A matching business execution context is required.');
        }
    }
}
