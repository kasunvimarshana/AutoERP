<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\Services\CustomerManagementServiceInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Modules\Customer\Domain\Constants\CustomerStatus;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerContactModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerCreditProfileModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerStatusHistoryModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerTaxProfileModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerUserAccountModel;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use RuntimeException;
use Throwable;

final class CustomerManagementService implements CustomerManagementServiceInterface
{
    public function __construct(
        private readonly CustomerModel $customers,
        private readonly CustomerContactModel $contacts,
        private readonly CustomerAddressModel $addresses,
        private readonly CustomerTaxProfileModel $taxProfiles,
        private readonly CustomerCreditProfileModel $creditProfiles,
        private readonly CustomerUserAccountModel $userAccounts,
        private readonly CustomerStatusHistoryModel $statusHistories,
        private readonly UserServiceInterface $userService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {
    }

    public function listCustomers(array $filters, int $perPage, int $page): Result
    {
        try {
            $tenantId = $this->tenantId();
            $resolvedPerPage = $perPage > 0 ? min($perPage, (int) config('customer.pagination.max_per_page', 200)) : 20;
            $resolvedPage = max(1, $page);

            $query = $this->customers->newQuery()->where('tenant_id', $tenantId);

            if (isset($filters['organization_unit_id']) && $filters['organization_unit_id'] !== null) {
                $query->where('organization_unit_id', (int) $filters['organization_unit_id']);
            }

            if (isset($filters['status']) && is_string($filters['status']) && trim($filters['status']) !== '') {
                $query->where('status', trim($filters['status']));
            }

            if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
                $query->where('is_active', (bool) $filters['is_active']);
            }

            if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
                $search = trim($filters['search']);
                $query->where(function ($inner) use ($search): void {
                    $inner->where('customer_code', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('display_name', 'like', '%' . $search . '%')
                        ->orWhere('registration_number', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            $paginator = $query->orderBy('customer_name')->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage);
            $items = [];
            foreach ($paginator->items() as $item) {
                $items[] = new DataRecord($item->toArray());
            }

            return Result::success(new PagedResult(
                $items,
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage(),
            ));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getCustomer(int|string $id): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            return Result::success($this->customerPayload($customer));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createCustomer(array $payload): Result
    {
        try {
            return DB::transaction(function () use ($payload): Result {
                $tenantId = $this->tenantId();
                $organizationUnitId = $this->organizationUnitId();

                $customerCode = $this->normalizeCode((string) ($payload['customer_code'] ?? ''));
                $customerName = $this->normalizeRequired((string) ($payload['customer_name'] ?? ''), 'Customer name');

                $existsByCustomerCode = $this->customers->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('customer_code', $customerCode)
                    ->exists();
                if ($existsByCustomerCode) {
                    return Result::failure(new Error(
                        CustomerErrorCode::INVALID_VALUE,
                        'Customer code already exists.',
                    ));
                }

                $registrationNumber = $this->normalizeNullable($payload['registration_number'] ?? null);
                if ($registrationNumber !== null) {
                    $existsByRegistration = $this->customers->newQuery()
                        ->where('tenant_id', $tenantId)
                        ->where('registration_number', $registrationNumber)
                        ->exists();
                    if ($existsByRegistration) {
                        return Result::failure(new Error(
                            CustomerErrorCode::INVALID_VALUE,
                            'Registration number already exists.',
                        ));
                    }
                }

                $status = $this->normalizeStatus($payload['status'] ?? CustomerStatus::DRAFT);
                $customer = $this->customers->newQuery()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                    'customer_code' => $customerCode,
                    'customer_name' => $customerName,
                    'legal_name' => $this->normalizeNullable($payload['legal_name'] ?? null),
                    'display_name' => $this->normalizeNullable($payload['display_name'] ?? null),
                    'customer_type' => $this->normalizeNullable($payload['customer_type'] ?? null) ?? 'business',
                    'category_id' => $this->toNullableInt($payload['category_id'] ?? null),
                    'registration_number' => $registrationNumber,
                    'tax_number' => $this->normalizeNullable($payload['tax_number'] ?? null),
                    'vat_number' => $this->normalizeNullable($payload['vat_number'] ?? null),
                    'email' => $this->normalizeNullable($payload['email'] ?? null),
                    'phone' => $this->normalizeNullable($payload['phone'] ?? null),
                    'mobile' => $this->normalizeNullable($payload['mobile'] ?? null),
                    'website' => $this->normalizeNullable($payload['website'] ?? null),
                    'default_currency_id' => $this->toNullableInt($payload['default_currency_id'] ?? null),
                    'default_payment_term_id' => $this->toNullableInt($payload['default_payment_term_id'] ?? null),
                    'default_receivable_account_id' => $this->toNullableInt($payload['default_receivable_account_id'] ?? null),
                    'default_income_account_id' => $this->toNullableInt($payload['default_income_account_id'] ?? null),
                    'credit_limit' => $payload['credit_limit'] ?? null,
                    'credit_days' => $this->toNullableInt($payload['credit_days'] ?? null),
                    'credit_hold' => (bool) ($payload['credit_hold'] ?? false),
                    'status' => $status,
                    'is_active' => (bool) ($payload['is_active'] ?? ($status === CustomerStatus::ACTIVE)),
                    'notes' => $this->normalizeNullable($payload['notes'] ?? null),
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                    'activated_by' => $status === CustomerStatus::ACTIVE ? $this->currentUser->currentUserId() : null,
                    'activated_at' => $status === CustomerStatus::ACTIVE ? now() : null,
                    'credit_hold_by' => (bool) ($payload['credit_hold'] ?? false) ? $this->currentUser->currentUserId() : null,
                    'credit_hold_at' => (bool) ($payload['credit_hold'] ?? false) ? now() : null,
                    'row_version' => 1,
                ]);

                $this->replaceContacts(
                    (int) $customer->id,
                    $payload['contacts'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );
                $this->replaceAddresses(
                    (int) $customer->id,
                    $payload['addresses'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );
                $this->upsertTaxProfile(
                    (int) $customer->id,
                    $payload['tax_profile'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );
                $this->upsertCreditProfile(
                    (int) $customer->id,
                    $payload['credit_profile'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );

                if (($payload['create_user'] ?? false) === true) {
                    $userAccessResult = $this->createCustomerUserAccess(
                        (int) $customer->id,
                        (array) ($payload['user_access'] ?? []),
                    );
                    if ($userAccessResult->isFailure()) {
                        return $userAccessResult;
                    }
                }

                if (isset($payload['link_user_id'])) {
                    $linkResult = $this->linkExistingUser((int) $customer->id, ['user_id' => $payload['link_user_id']]);
                    if ($linkResult->isFailure()) {
                        return $linkResult;
                    }
                }

                $this->recordStatusHistory(
                    (int) $customer->id,
                    null,
                    $status,
                    $payload['status_reason'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );

                $fresh = $this->findCustomerInScope((int) $customer->id);
                if ($fresh === null) {
                    return $this->notFound();
                }

                return Result::success($this->customerPayload($fresh));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateCustomer(int|string $id, array $payload): Result
    {
        try {
            return DB::transaction(function () use ($id, $payload): Result {
                $customer = $this->findCustomerInScope($id);
                if ($customer === null) {
                    return $this->notFound();
                }

                $tenantId = (int) $customer->tenant_id;
                $organizationUnitId = (int) ($customer->organization_unit_id ?? $this->organizationUnitId());

                $changes = [
                    'updated_by' => $this->currentUser->currentUserId(),
                    'row_version' => ((int) $customer->row_version) + 1,
                ];

                if (array_key_exists('customer_name', $payload)) {
                    $changes['customer_name'] = $this->normalizeRequired((string) $payload['customer_name'], 'Customer name');
                }
                if (array_key_exists('legal_name', $payload)) {
                    $changes['legal_name'] = $this->normalizeNullable($payload['legal_name']);
                }
                if (array_key_exists('display_name', $payload)) {
                    $changes['display_name'] = $this->normalizeNullable($payload['display_name']);
                }
                if (array_key_exists('customer_type', $payload)) {
                    $changes['customer_type'] = $this->normalizeNullable($payload['customer_type']) ?? 'business';
                }
                if (array_key_exists('category_id', $payload)) {
                    $changes['category_id'] = $this->toNullableInt($payload['category_id']);
                }
                if (array_key_exists('registration_number', $payload)) {
                    $registrationNumber = $this->normalizeNullable($payload['registration_number']);
                    if ($registrationNumber !== null) {
                        $existsByRegistration = $this->customers->newQuery()
                            ->where('tenant_id', $tenantId)
                            ->where('registration_number', $registrationNumber)
                            ->where('id', '!=', (int) $customer->id)
                            ->exists();
                        if ($existsByRegistration) {
                            return Result::failure(new Error(
                                CustomerErrorCode::INVALID_VALUE,
                                'Registration number already exists.',
                            ));
                        }
                    }
                    $changes['registration_number'] = $registrationNumber;
                }

                foreach (['tax_number', 'vat_number', 'email', 'phone', 'mobile', 'website', 'notes'] as $nullableText) {
                    if (array_key_exists($nullableText, $payload)) {
                        $changes[$nullableText] = $this->normalizeNullable($payload[$nullableText]);
                    }
                }

                foreach (
                    ['default_currency_id', 'default_payment_term_id', 'default_receivable_account_id', 'default_income_account_id'] as $nullableInt
                ) {
                    if (array_key_exists($nullableInt, $payload)) {
                        $changes[$nullableInt] = $this->toNullableInt($payload[$nullableInt]);
                    }
                }

                if (array_key_exists('credit_limit', $payload)) {
                    $changes['credit_limit'] = $payload['credit_limit'];
                }
                if (array_key_exists('credit_days', $payload)) {
                    $changes['credit_days'] = $this->toNullableInt($payload['credit_days']);
                }
                if (array_key_exists('credit_hold', $payload)) {
                    $changes['credit_hold'] = (bool) $payload['credit_hold'];
                    $changes['credit_hold_by'] = (bool) $payload['credit_hold'] ? $this->currentUser->currentUserId() : null;
                    $changes['credit_hold_at'] = (bool) $payload['credit_hold'] ? now() : null;
                }
                if (array_key_exists('metadata', $payload)) {
                    $changes['metadata'] = $this->normalizeArray($payload['metadata']);
                }

                $customer->fill($changes);
                $customer->save();

                if (array_key_exists('contacts', $payload)) {
                    $this->replaceContacts((int) $customer->id, $payload['contacts'], $tenantId, $organizationUnitId);
                }
                if (array_key_exists('addresses', $payload)) {
                    $this->replaceAddresses((int) $customer->id, $payload['addresses'], $tenantId, $organizationUnitId);
                }
                if (array_key_exists('tax_profile', $payload)) {
                    $this->upsertTaxProfile((int) $customer->id, $payload['tax_profile'], $tenantId, $organizationUnitId);
                }
                if (array_key_exists('credit_profile', $payload)) {
                    $this->upsertCreditProfile((int) $customer->id, $payload['credit_profile'], $tenantId, $organizationUnitId);
                }

                if (($payload['create_user'] ?? false) === true) {
                    $userAccessResult = $this->createCustomerUserAccess(
                        (int) $customer->id,
                        (array) ($payload['user_access'] ?? []),
                    );
                    if ($userAccessResult->isFailure()) {
                        return $userAccessResult;
                    }
                }

                if (isset($payload['link_user_id'])) {
                    $linkResult = $this->linkExistingUser((int) $customer->id, ['user_id' => $payload['link_user_id']]);
                    if ($linkResult->isFailure()) {
                        return $linkResult;
                    }
                }

                $fresh = $this->findCustomerInScope((int) $customer->id);
                if ($fresh === null) {
                    return $this->notFound();
                }

                return Result::success($this->customerPayload($fresh));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function changeStatus(int|string $id, string $toStatus, ?string $reason = null): Result
    {
        try {
            return DB::transaction(function () use ($id, $toStatus, $reason): Result {
                $customer = $this->findCustomerInScope($id);
                if ($customer === null) {
                    return $this->notFound();
                }

                $targetStatus = $this->normalizeStatus($toStatus);
                $fromStatus = (string) $customer->status;
                if ($fromStatus === $targetStatus) {
                    return Result::success($this->customerPayload($customer));
                }

                $customer->status = $targetStatus;
                $customer->is_active = $targetStatus === CustomerStatus::ACTIVE;
                $customer->updated_by = $this->currentUser->currentUserId();
                $customer->row_version = ((int) $customer->row_version) + 1;

                if ($targetStatus === CustomerStatus::ACTIVE) {
                    $customer->activated_at = now();
                    $customer->activated_by = $this->currentUser->currentUserId();
                    $customer->deactivated_at = null;
                    $customer->blocked_at = null;
                }
                if ($targetStatus === CustomerStatus::INACTIVE) {
                    $customer->deactivated_at = now();
                    $customer->deactivated_by = $this->currentUser->currentUserId();
                }
                if ($targetStatus === CustomerStatus::BLOCKED) {
                    $customer->blocked_at = now();
                    $customer->blocked_by = $this->currentUser->currentUserId();
                }
                if ($targetStatus === CustomerStatus::ARCHIVED) {
                    $customer->archived_at = now();
                    $customer->archived_by = $this->currentUser->currentUserId();
                }

                $customer->save();

                $this->recordStatusHistory(
                    (int) $customer->id,
                    $fromStatus,
                    $targetStatus,
                    $reason,
                    (int) $customer->tenant_id,
                    $this->toNullableInt($customer->organization_unit_id),
                );

                return Result::success($this->customerPayload($customer));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function safeDeleteCustomer(int|string $id): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            if ((string) $customer->status !== CustomerStatus::ARCHIVED) {
                return Result::failure(new Error(
                    CustomerErrorCode::INVALID_VALUE,
                    'Customer must be archived before deletion.',
                ));
            }

            $customer->delete();

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function lookupCustomers(string $search, int $limit = 20): Result
    {
        try {
            $tenantId = $this->tenantId();
            $query = $this->customers->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('status', CustomerStatus::ACTIVE)
                ->where('is_active', true)
                ->orderBy('customer_name');

            $term = trim($search);
            if ($term !== '') {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('customer_name', 'like', '%' . $term . '%')
                        ->orWhere('customer_code', 'like', '%' . $term . '%')
                        ->orWhere('registration_number', 'like', '%' . $term . '%');
                });
            }

            $results = $query->limit(max(1, min($limit, 100)))
                ->get(['id', 'customer_code', 'customer_name', 'email', 'phone']);

            return Result::success($results->map(fn ($item): array => $this->recordToArray($item))->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function validateCustomerForContext(int|string $id, string $context): Result
    {
        return $this->validateCustomerForTransaction($id, $context);
    }

    public function getFinanceDefaults(int|string $id): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            return Result::success([
                'customer_id' => (int) $customer->id,
                'default_currency_id' => $customer->default_currency_id,
                'default_payment_term_id' => $customer->default_payment_term_id,
                'default_receivable_account_id' => $customer->default_receivable_account_id,
                'default_income_account_id' => $customer->default_income_account_id,
                'credit_limit' => $customer->credit_limit,
                'credit_days' => $customer->credit_days,
                'credit_hold' => (bool) $customer->credit_hold,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateFinanceDefaults(int|string $id, array $payload): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            $customer->fill([
                'default_currency_id' => $this->toNullableInt($payload['default_currency_id'] ?? null),
                'default_payment_term_id' => $this->toNullableInt($payload['default_payment_term_id'] ?? null),
                'default_receivable_account_id' => $this->toNullableInt($payload['default_receivable_account_id'] ?? null),
                'default_income_account_id' => $this->toNullableInt($payload['default_income_account_id'] ?? null),
                'credit_limit' => $payload['credit_limit'] ?? null,
                'credit_days' => $this->toNullableInt($payload['credit_days'] ?? null),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => ((int) $customer->row_version) + 1,
            ]);
            $customer->save();

            return $this->getFinanceDefaults((int) $customer->id);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getCustomerTaxProfile(int|string $id): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            $taxProfile = $this->taxProfiles->newQuery()
                ->where('tenant_id', (int) $customer->tenant_id)
                ->where('customer_id', (int) $customer->id)
                ->first();

            return Result::success($taxProfile !== null ? $this->recordToArray($taxProfile) : null);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function checkCustomerCreditLimit(int|string $id, ?float $requestedAmount = null): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            $creditLimit = (float) ($customer->credit_limit ?? 0.0);
            $available = $creditLimit;
            $requested = $requestedAmount ?? 0.0;

            return Result::success([
                'customer_id' => (int) $customer->id,
                'credit_limit' => $creditLimit,
                'available_credit' => $available,
                'requested_amount' => $requested,
                'credit_hold' => (bool) $customer->credit_hold,
                'is_allowed' => ! (bool) $customer->credit_hold && ($requested <= $available),
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listCustomerUserAccounts(int|string $customerId): Result
    {
        try {
            $customer = $this->findCustomerInScope($customerId);
            if ($customer === null) {
                return $this->notFound();
            }

            $accounts = $this->userAccounts->newQuery()
                ->where('tenant_id', (int) $customer->tenant_id)
                ->where('customer_id', (int) $customer->id)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->get();

            return Result::success($accounts->map(fn ($item): array => $this->recordToArray($item))->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createCustomerUserAccess(int|string $customerId, array $payload): Result
    {
        try {
            return DB::transaction(function () use ($customerId, $payload): Result {
                $customer = $this->findCustomerInScope($customerId);
                if ($customer === null) {
                    return $this->notFound();
                }

                $tenantId = (int) $customer->tenant_id;
                $organizationUnitId = $this->toNullableInt($customer->organization_unit_id) ?? $this->organizationUnitId();

                $userPayload = (array) ($payload['user'] ?? []);
                $email = $this->normalizeRequired((string) ($userPayload['email'] ?? ''), 'User email');
                $fullName = $this->normalizeRequired((string) ($userPayload['name'] ?? $customer->customer_name), 'User name');
                $nameParts = preg_split('/\s+/', trim($fullName)) ?: [];
                $firstName = $nameParts[0] ?? $fullName;
                $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : null;

                $password = (string) ($userPayload['password'] ?? Str::random(32));

                $createResult = $this->userService->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => $password,
                    'status' => 'active',
                    'metadata' => [
                        'created_from' => 'customer_module',
                        'customer_id' => (int) $customer->id,
                    ],
                ]);

                if ($createResult->isFailure()) {
                    return Result::failure($createResult->errorOrFail());
                }

                $createdUser = $createResult->valueOrFail();
                $createdUserId = $createdUser instanceof DataRecord
                    ? (int) $createdUser->id()
                    : (int) ($createdUser['id'] ?? 0);

                if ($createdUserId <= 0) {
                    return Result::failure(new Error(
                        CustomerErrorCode::INVALID_VALUE,
                        'User creation did not return an identifier.',
                    ));
                }

                return $this->linkExistingUser((int) $customer->id, [
                    'user_id' => $createdUserId,
                    'access_role' => $payload['access_role'] ?? 'customer_portal',
                    'is_primary' => $payload['is_primary'] ?? false,
                    'metadata' => $payload['metadata'] ?? null,
                    'invited' => (bool) ($payload['invited'] ?? true),
                ]);
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function linkExistingUser(int|string $customerId, array $payload): Result
    {
        try {
            $customer = $this->findCustomerInScope($customerId);
            if ($customer === null) {
                return $this->notFound();
            }

            $userId = $this->toNullableInt($payload['user_id'] ?? null);
            if ($userId === null) {
                return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, 'User id is required for linking.'));
            }

            $tenantId = (int) $customer->tenant_id;
            $exists = $this->userAccounts->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('customer_id', (int) $customer->id)
                ->where('user_id', $userId)
                ->exists();
            if ($exists) {
                return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, 'User is already linked to customer.'));
            }

            $isPrimary = (bool) ($payload['is_primary'] ?? false);
            if ($isPrimary) {
                $this->userAccounts->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('customer_id', (int) $customer->id)
                    ->update(['is_primary' => false, 'updated_at' => now()]);
            }

            $isInvited = (bool) ($payload['invited'] ?? false);
            $status = $isInvited ? 'invited' : 'active';

            $link = $this->userAccounts->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($customer->organization_unit_id) ?? $this->organizationUnitId(),
                'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                'customer_id' => (int) $customer->id,
                'user_id' => $userId,
                'access_role' => $this->normalizeNullable($payload['access_role'] ?? null) ?? 'customer_portal',
                'is_primary' => $isPrimary,
                'access_status' => $status,
                'invited_at' => $isInvited ? now() : null,
                'activated_at' => $isInvited ? null : now(),
                'linked_user_by' => $this->currentUser->currentUserId(),
                'invited_by' => $isInvited ? $this->currentUser->currentUserId() : null,
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);

            return Result::success(new DataRecord($link->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function deactivateCustomerUserAccess(int|string $customerId, int|string $accessId, array $payload): Result
    {
        try {
            $customer = $this->findCustomerInScope($customerId);
            if ($customer === null) {
                return $this->notFound();
            }

            $access = $this->userAccounts->newQuery()
                ->where('tenant_id', (int) $customer->tenant_id)
                ->where('customer_id', (int) $customer->id)
                ->where('id', (int) $accessId)
                ->first();

            if ($access === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'Customer user access not found.'));
            }

            $metadata = $this->normalizeArray($access->metadata ?? null) ?? [];
            if (isset($payload['reason']) && is_string($payload['reason']) && trim($payload['reason']) !== '') {
                $metadata['deactivation_reason'] = trim($payload['reason']);
            }

            $access->fill([
                'access_status' => 'inactive',
                'is_primary' => false,
                'deactivated_at' => now(),
                'metadata' => $metadata,
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => ((int) $access->row_version) + 1,
            ]);
            $access->save();

            return Result::success(new DataRecord($access->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function unlinkCustomerUserAccess(int|string $customerId, int|string $accessId): Result
    {
        try {
            $customer = $this->findCustomerInScope($customerId);
            if ($customer === null) {
                return $this->notFound();
            }

            $access = $this->userAccounts->newQuery()
                ->where('tenant_id', (int) $customer->tenant_id)
                ->where('customer_id', (int) $customer->id)
                ->where('id', (int) $accessId)
                ->first();

            if ($access === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'Customer user access not found.'));
            }

            $access->delete();

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    private function validateCustomerForTransaction(int|string $id, string $channel): Result
    {
        try {
            $customer = $this->findCustomerInScope($id);
            if ($customer === null) {
                return $this->notFound();
            }

            $errors = [];
            if ((string) $customer->status !== CustomerStatus::ACTIVE || ! (bool) $customer->is_active) {
                $errors[] = 'Customer must be active.';
            }
            if ((bool) $customer->credit_hold) {
                $errors[] = 'Customer is currently on credit hold.';
            }

            $resolvedContext = $this->normalizeContext($channel);

            return Result::success([
                'customer_id' => (int) $customer->id,
                'context' => $resolvedContext,
                'channel' => $resolvedContext,
                'is_valid' => $errors === [],
                'errors' => $errors,
                'finance_defaults' => [
                    'default_currency_id' => $customer->default_currency_id,
                    'default_payment_term_id' => $customer->default_payment_term_id,
                    'default_receivable_account_id' => $customer->default_receivable_account_id,
                    'default_income_account_id' => $customer->default_income_account_id,
                ],
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    private function customerPayload(CustomerModel $customer): DataRecord
    {
        $customerId = (int) $customer->id;
        $tenantId = (int) $customer->tenant_id;

        $payload = $customer->toArray();
        $payload['contacts'] = $this->contacts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();
        $payload['addresses'] = $this->addresses->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();
        $payload['tax_profile'] = $this->taxProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->first()?->toArray();
        $payload['credit_profile'] = $this->creditProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->first()?->toArray();
        $payload['user_accounts'] = $this->userAccounts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();

        return new DataRecord($payload);
    }

    private function findCustomerInScope(int|string $id): ?CustomerModel
    {
        return $this->customers->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('id', (int) $id)
            ->first();
    }

    private function replaceContacts(int $customerId, mixed $contacts, int $tenantId, ?int $organizationUnitId): void
    {
        if (! is_array($contacts)) {
            return;
        }

        $this->contacts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->delete();

        $hasPrimary = false;
        foreach ($contacts as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $isPrimary = (bool) ($entry['is_primary'] ?? false);
            if ($isPrimary) {
                $hasPrimary = true;
            }

            $this->contacts->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->normalizeArray($entry['metadata'] ?? null),
                'customer_id' => $customerId,
                'contact_name' => $this->normalizeRequired((string) ($entry['contact_name'] ?? ''), 'Contact name'),
                'designation' => $this->normalizeNullable($entry['designation'] ?? null),
                'department' => $this->normalizeNullable($entry['department'] ?? null),
                'email' => $this->normalizeNullable($entry['email'] ?? null),
                'phone' => $this->normalizeNullable($entry['phone'] ?? null),
                'mobile' => $this->normalizeNullable($entry['mobile'] ?? null),
                'is_primary' => $isPrimary,
                'is_active' => (bool) ($entry['is_active'] ?? true),
                'notes' => $this->normalizeNullable($entry['notes'] ?? null),
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);
        }

        if (! $hasPrimary) {
            $first = $this->contacts->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->first();
            if ($first !== null) {
                $first->is_primary = true;
                $first->save();
            }
        }
    }

    private function replaceAddresses(int $customerId, mixed $addresses, int $tenantId, ?int $organizationUnitId): void
    {
        if (! is_array($addresses)) {
            return;
        }

        $this->addresses->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->delete();

        foreach ($addresses as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $this->addresses->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->normalizeArray($entry['metadata'] ?? null),
                'customer_id' => $customerId,
                'address_type' => $this->normalizeNullable($entry['address_type'] ?? null) ?? 'billing',
                'label' => $this->normalizeNullable($entry['label'] ?? null),
                'contact_person' => $this->normalizeNullable($entry['contact_person'] ?? null),
                'contact_phone' => $this->normalizeNullable($entry['contact_phone'] ?? null),
                'address_line_1' => $this->normalizeRequired((string) ($entry['address_line_1'] ?? ''), 'Address line 1'),
                'address_line_2' => $this->normalizeNullable($entry['address_line_2'] ?? null),
                'city' => $this->normalizeRequired((string) ($entry['city'] ?? ''), 'City'),
                'state_province' => $this->normalizeNullable($entry['state_province'] ?? null),
                'postal_code' => $this->normalizeRequired((string) ($entry['postal_code'] ?? ''), 'Postal code'),
                'country_id' => $this->toNullableInt($entry['country_id'] ?? null),
                'country_name' => $this->normalizeNullable($entry['country_name'] ?? null),
                'is_primary' => (bool) ($entry['is_primary'] ?? false),
                'is_primary_billing' => (bool) ($entry['is_primary_billing'] ?? false),
                'is_primary_shipping' => (bool) ($entry['is_primary_shipping'] ?? false),
                'is_active' => (bool) ($entry['is_active'] ?? true),
                'geo_lat' => $entry['geo_lat'] ?? null,
                'geo_lng' => $entry['geo_lng'] ?? null,
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);
        }
    }

    private function upsertTaxProfile(int $customerId, mixed $taxProfile, int $tenantId, ?int $organizationUnitId): void
    {
        if (! is_array($taxProfile)) {
            return;
        }

        $existing = $this->taxProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->first();

        $attributes = [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->normalizeArray($taxProfile['metadata'] ?? null),
            'tax_registration_number' => $this->normalizeNullable($taxProfile['tax_registration_number'] ?? null),
            'vat_number' => $this->normalizeNullable($taxProfile['vat_number'] ?? null),
            'tax_group_id' => $this->toNullableInt($taxProfile['tax_group_id'] ?? null),
            'tax_exempt' => (bool) ($taxProfile['tax_exempt'] ?? false),
            'exemption_certificate_reference' => $this->normalizeNullable($taxProfile['exemption_certificate_reference'] ?? null),
            'valid_from' => $taxProfile['valid_from'] ?? null,
            'valid_to' => $taxProfile['valid_to'] ?? null,
            'is_active' => (bool) ($taxProfile['is_active'] ?? true),
            'updated_by' => $this->currentUser->currentUserId(),
        ];

        if ($existing === null) {
            $this->taxProfiles->newQuery()->create(array_merge($attributes, [
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'created_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]));

            return;
        }

        $existing->fill(array_merge($attributes, [
            'row_version' => ((int) $existing->row_version) + 1,
        ]));
        $existing->save();
    }

    private function upsertCreditProfile(int $customerId, mixed $creditProfile, int $tenantId, ?int $organizationUnitId): void
    {
        if (! is_array($creditProfile)) {
            return;
        }

        $existing = $this->creditProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->first();

        $creditHold = (bool) ($creditProfile['credit_hold'] ?? false);
        $attributes = [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->normalizeArray($creditProfile['metadata'] ?? null),
            'credit_limit' => $creditProfile['credit_limit'] ?? null,
            'credit_days' => $this->toNullableInt($creditProfile['credit_days'] ?? null),
            'credit_hold' => $creditHold,
            'credit_hold_reason' => $this->normalizeNullable($creditProfile['credit_hold_reason'] ?? null),
            'allow_credit_override' => (bool) ($creditProfile['allow_credit_override'] ?? false),
            'credit_hold_by' => $creditHold ? $this->currentUser->currentUserId() : null,
            'credit_hold_at' => $creditHold ? now() : null,
            'is_active' => (bool) ($creditProfile['is_active'] ?? true),
            'updated_by' => $this->currentUser->currentUserId(),
        ];

        if ($existing === null) {
            $this->creditProfiles->newQuery()->create(array_merge($attributes, [
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'created_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]));

            return;
        }

        $existing->fill(array_merge($attributes, [
            'row_version' => ((int) $existing->row_version) + 1,
        ]));
        $existing->save();
    }

    private function recordStatusHistory(
        int $customerId,
        ?string $fromStatus,
        string $toStatus,
        mixed $reason,
        int $tenantId,
        ?int $organizationUnitId,
    ): void {
        $this->statusHistories->newQuery()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'metadata' => null,
            'customer_id' => $customerId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => is_string($reason) && trim($reason) !== '' ? trim($reason) : null,
            'changed_by' => $this->currentUser->currentUserId(),
            'changed_at' => now(),
            'row_version' => 1,
        ]);
    }

    private function tenantId(): int
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null || $tenantId <= 0) {
            throw new RuntimeException('Current tenant context is required.');
        }

        return $tenantId;
    }

    private function organizationUnitId(): ?int
    {
        return $this->currentOrganizationUnit->currentOrganizationUnitId();
    }

    private function normalizeStatus(mixed $status): string
    {
        $resolved = is_string($status) ? strtolower(trim($status)) : '';
        if ($resolved === '') {
            $resolved = CustomerStatus::DRAFT;
        }
        if (! in_array($resolved, CustomerStatus::values(), true)) {
            throw new RuntimeException('Invalid customer status value.');
        }

        return $resolved;
    }

    private function normalizeRequired(string $value, string $label): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new RuntimeException($label . ' is required.');
        }

        return $normalized;
    }

    private function normalizeCode(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            throw new RuntimeException('Customer code is required.');
        }

        return $normalized;
    }

    private function normalizeContext(string $value): string
    {
        $normalized = Str::of($value)->trim()->lower()->replace([' ', '-'], '_')->toString();

        return $normalized === '' ? 'generic' : $normalized;
    }

    private function normalizeNullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function recordToArray(mixed $item): array
    {
        if (is_object($item) && method_exists($item, 'toArray')) {
            /** @var array<string, mixed> $asArray */
            $asArray = $item->toArray();

            return $asArray;
        }

        if (is_array($item)) {
            return $item;
        }

        return (array) $item;
    }

    private function notFound(): Result
    {
        return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'Customer not found.'));
    }

    private function failure(string $message): Result
    {
        return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $message));
    }
}
