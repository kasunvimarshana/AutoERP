<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\Services\SupplierManagementServiceInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Modules\Supplier\Domain\Constants\SupplierStatus;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierBankAccountModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierStatusHistoryModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierTaxProfileModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierUserAccountModel;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use Throwable;

final class SupplierManagementService implements SupplierManagementServiceInterface
{
    public function __construct(
        private readonly SupplierModel $suppliers,
        private readonly SupplierContactModel $contacts,
        private readonly SupplierAddressModel $addresses,
        private readonly SupplierBankAccountModel $bankAccounts,
        private readonly SupplierTaxProfileModel $taxProfiles,
        private readonly SupplierUserAccountModel $userAccounts,
        private readonly SupplierStatusHistoryModel $statusHistories,
        private readonly UserServiceInterface $userService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function listSuppliers(array $filters, int $perPage, int $page): Result
    {
        try {
            $tenantId = $this->tenantId();
            $resolvedPerPage = $perPage > 0 ? min($perPage, (int) config('supplier.pagination.max_per_page', 200)) : 20;
            $resolvedPage = max(1, $page);

            $query = $this->suppliers->newQuery()->where('tenant_id', $tenantId);

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
                    $inner->where('supplier_code', 'like', '%'.$search.'%')
                        ->orWhere('supplier_name', 'like', '%'.$search.'%')
                        ->orWhere('display_name', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            }

            $paginator = $query->orderBy('supplier_name')->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage);
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

    public function getSupplier(int|string $id): Result
    {
        try {
            $supplier = $this->findSupplierInScope($id);
            if ($supplier === null) {
                return $this->notFound();
            }

            return Result::success($this->supplierPayload($supplier));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createSupplier(array $payload): Result
    {
        try {
            return DB::transaction(function () use ($payload): Result {
                $tenantId = $this->tenantId();
                $organizationUnitId = $this->organizationUnitId();

                $supplierCode = $this->normalizeCode((string) ($payload['supplier_code'] ?? ''));
                $supplierName = $this->normalizeRequired((string) ($payload['supplier_name'] ?? ''), 'Supplier name');

                $existsBySupplierCode = $this->suppliers->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('supplier_code', $supplierCode)
                    ->exists();

                if ($existsBySupplierCode) {
                    return Result::failure(new Error(
                        SupplierErrorCode::INVALID_VALUE,
                        'Supplier code already exists.',
                    ));
                }

                $registrationNumber = $this->normalizeNullable($payload['registration_number'] ?? null);
                if ($registrationNumber !== null) {
                    $existsByRegistration = $this->suppliers->newQuery()
                        ->where('tenant_id', $tenantId)
                        ->where('registration_number', $registrationNumber)
                        ->exists();
                    if ($existsByRegistration) {
                        return Result::failure(new Error(
                            SupplierErrorCode::INVALID_VALUE,
                            'Registration number already exists.',
                        ));
                    }
                }

                $status = $this->normalizeStatus($payload['status'] ?? SupplierStatus::DRAFT);
                $supplier = $this->suppliers->newQuery()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                    'supplier_code' => $supplierCode,
                    'supplier_name' => $supplierName,
                    'legal_name' => $this->normalizeNullable($payload['legal_name'] ?? null),
                    'display_name' => $this->normalizeNullable($payload['display_name'] ?? null),
                    'supplier_type' => $this->normalizeNullable($payload['supplier_type'] ?? null) ?? 'business',
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
                    'default_payable_account_id' => $this->toNullableInt(
                        $payload['default_payable_account_id'] ?? null,
                    ),
                    'default_expense_account_id' => $this->toNullableInt(
                        $payload['default_expense_account_id'] ?? null,
                    ),
                    'credit_limit' => $payload['credit_limit'] ?? null,
                    'status' => $status,
                    'is_active' => (bool) ($payload['is_active'] ?? ($status === SupplierStatus::ACTIVE)),
                    'notes' => $this->normalizeNullable($payload['notes'] ?? null),
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                    'activated_by' => $status === SupplierStatus::ACTIVE ? $this->currentUser->currentUserId() : null,
                    'activated_at' => $status === SupplierStatus::ACTIVE ? now() : null,
                    'row_version' => 1,
                ]);

                $this->replaceContacts(
                    (int) $supplier->id,
                    $payload['contacts'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );
                $this->replaceAddresses(
                    (int) $supplier->id,
                    $payload['addresses'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );
                $this->replaceBankAccounts(
                    (int) $supplier->id,
                    $payload['bank_accounts'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );
                $this->upsertTaxProfile(
                    (int) $supplier->id,
                    $payload['tax_profile'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );

                if (($payload['create_user_access'] ?? false) === true) {
                    $userAccessResult = $this->createSupplierUserAccess(
                        (int) $supplier->id,
                        (array) ($payload['user_access'] ?? []),
                    );
                    if ($userAccessResult->isFailure()) {
                        return $userAccessResult;
                    }
                }

                if (isset($payload['link_user_id'])) {
                    $linkResult = $this->linkExistingUser((int) $supplier->id, ['user_id' => $payload['link_user_id']]);
                    if ($linkResult->isFailure()) {
                        return $linkResult;
                    }
                }

                $this->recordStatusHistory(
                    (int) $supplier->id,
                    null,
                    $status,
                    $payload['status_reason'] ?? null,
                    $tenantId,
                    $organizationUnitId,
                );

                $fresh = $this->findSupplierInScope((int) $supplier->id);
                if ($fresh === null) {
                    return $this->notFound();
                }

                return Result::success($this->supplierPayload($fresh));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateSupplier(int|string $id, array $payload): Result
    {
        try {
            return DB::transaction(function () use ($id, $payload): Result {
                $supplier = $this->findSupplierInScope($id);
                if ($supplier === null) {
                    return $this->notFound();
                }

                $tenantId = (int) $supplier->tenant_id;
                $organizationUnitId = (int) ($supplier->organization_unit_id ?? $this->organizationUnitId());

                $changes = [
                    'updated_by' => $this->currentUser->currentUserId(),
                    'row_version' => ((int) $supplier->row_version) + 1,
                ];

                if (array_key_exists('supplier_name', $payload)) {
                    $changes['supplier_name'] = $this->normalizeRequired(
                        (string) $payload['supplier_name'],
                        'Supplier name',
                    );
                }
                if (array_key_exists('legal_name', $payload)) {
                    $changes['legal_name'] = $this->normalizeNullable($payload['legal_name']);
                }
                if (array_key_exists('display_name', $payload)) {
                    $changes['display_name'] = $this->normalizeNullable($payload['display_name']);
                }
                if (array_key_exists('supplier_type', $payload)) {
                    $changes['supplier_type'] = $this->normalizeNullable($payload['supplier_type']) ?? 'business';
                }
                if (array_key_exists('category_id', $payload)) {
                    $changes['category_id'] = $this->toNullableInt($payload['category_id']);
                }
                if (array_key_exists('registration_number', $payload)) {
                    $registrationNumber = $this->normalizeNullable($payload['registration_number']);
                    if ($registrationNumber !== null) {
                        $existsByRegistration = $this->suppliers->newQuery()
                            ->where('tenant_id', $tenantId)
                            ->where('registration_number', $registrationNumber)
                            ->where('id', '!=', (int) $supplier->id)
                            ->exists();
                        if ($existsByRegistration) {
                            return Result::failure(new Error(
                                SupplierErrorCode::INVALID_VALUE,
                                'Registration number already exists.',
                            ));
                        }
                    }
                    $changes['registration_number'] = $registrationNumber;
                }

                foreach (
                    ['tax_number', 'vat_number', 'email', 'phone', 'mobile', 'website', 'notes'] as $nullableText
                ) {
                    if (array_key_exists($nullableText, $payload)) {
                        $changes[$nullableText] = $this->normalizeNullable($payload[$nullableText]);
                    }
                }

                foreach (
                    [
                        'default_currency_id',
                        'default_payment_term_id',
                        'default_payable_account_id',
                        'default_expense_account_id',
                    ] as $nullableInt
                ) {
                    if (array_key_exists($nullableInt, $payload)) {
                        $changes[$nullableInt] = $this->toNullableInt($payload[$nullableInt]);
                    }
                }

                if (array_key_exists('credit_limit', $payload)) {
                    $changes['credit_limit'] = $payload['credit_limit'];
                }

                if (array_key_exists('metadata', $payload)) {
                    $changes['metadata'] = $this->normalizeArray($payload['metadata']);
                }

                $supplier->fill($changes);
                $supplier->save();

                if (array_key_exists('contacts', $payload)) {
                    $this->replaceContacts((int) $supplier->id, $payload['contacts'], $tenantId, $organizationUnitId);
                }
                if (array_key_exists('addresses', $payload)) {
                    $this->replaceAddresses((int) $supplier->id, $payload['addresses'], $tenantId, $organizationUnitId);
                }
                if (array_key_exists('bank_accounts', $payload)) {
                    $this->replaceBankAccounts(
                        (int) $supplier->id,
                        $payload['bank_accounts'],
                        $tenantId,
                        $organizationUnitId,
                    );
                }
                if (array_key_exists('tax_profile', $payload)) {
                    $this->upsertTaxProfile(
                        (int) $supplier->id,
                        $payload['tax_profile'],
                        $tenantId,
                        $organizationUnitId,
                    );
                }

                if (($payload['create_user_access'] ?? false) === true) {
                    $userAccessResult = $this->createSupplierUserAccess(
                        (int) $supplier->id,
                        (array) ($payload['user_access'] ?? []),
                    );
                    if ($userAccessResult->isFailure()) {
                        return $userAccessResult;
                    }
                }

                if (isset($payload['link_user_id'])) {
                    $linkResult = $this->linkExistingUser((int) $supplier->id, ['user_id' => $payload['link_user_id']]);
                    if ($linkResult->isFailure()) {
                        return $linkResult;
                    }
                }

                $fresh = $this->findSupplierInScope((int) $supplier->id);
                if ($fresh === null) {
                    return $this->notFound();
                }

                return Result::success($this->supplierPayload($fresh));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function changeStatus(int|string $id, string $toStatus, ?string $reason = null): Result
    {
        try {
            return DB::transaction(function () use ($id, $toStatus, $reason): Result {
                $supplier = $this->findSupplierInScope($id);
                if ($supplier === null) {
                    return $this->notFound();
                }

                $targetStatus = $this->normalizeStatus($toStatus);
                $fromStatus = (string) $supplier->status;
                if ($fromStatus === $targetStatus) {
                    return Result::success($this->supplierPayload($supplier));
                }

                $supplier->status = $targetStatus;
                $supplier->is_active = $targetStatus === SupplierStatus::ACTIVE;
                $supplier->updated_by = $this->currentUser->currentUserId();
                $supplier->row_version = ((int) $supplier->row_version) + 1;

                if ($targetStatus === SupplierStatus::ACTIVE) {
                    $supplier->activated_at = now();
                    $supplier->activated_by = $this->currentUser->currentUserId();
                    $supplier->deactivated_at = null;
                    $supplier->blocked_at = null;
                }
                if ($targetStatus === SupplierStatus::INACTIVE) {
                    $supplier->deactivated_at = now();
                    $supplier->deactivated_by = $this->currentUser->currentUserId();
                }
                if ($targetStatus === SupplierStatus::BLOCKED) {
                    $supplier->blocked_at = now();
                    $supplier->blocked_by = $this->currentUser->currentUserId();
                }
                if ($targetStatus === SupplierStatus::ARCHIVED) {
                    $supplier->archived_at = now();
                    $supplier->archived_by = $this->currentUser->currentUserId();
                }

                $supplier->save();

                $this->recordStatusHistory(
                    (int) $supplier->id,
                    $fromStatus,
                    $targetStatus,
                    $reason,
                    (int) $supplier->tenant_id,
                    $this->toNullableInt($supplier->organization_unit_id),
                );

                return Result::success($this->supplierPayload($supplier));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function safeDeleteSupplier(int|string $id): Result
    {
        try {
            $supplier = $this->findSupplierInScope($id);
            if ($supplier === null) {
                return $this->notFound();
            }

            if ((string) $supplier->status !== SupplierStatus::ARCHIVED) {
                return Result::failure(new Error(
                    SupplierErrorCode::INVALID_VALUE,
                    'Supplier must be archived before deletion.',
                ));
            }

            $supplier->delete();

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function lookupSuppliers(string $search, int $limit = 20): Result
    {
        try {
            $tenantId = $this->tenantId();
            $query = $this->suppliers->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('status', SupplierStatus::ACTIVE)
                ->where('is_active', true)
                ->orderBy('supplier_name');

            $term = trim($search);
            if ($term !== '') {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('supplier_name', 'like', '%'.$term.'%')
                        ->orWhere('supplier_code', 'like', '%'.$term.'%')
                        ->orWhere('registration_number', 'like', '%'.$term.'%');
                });
            }

            $results = $query->limit(max(1, min($limit, 100)))->get(['id', 'supplier_code', 'supplier_name', 'email']);

            return Result::success($results->map(fn ($item): array => $this->recordToArray($item))->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function validateSupplierForContext(int|string $id, string $context): Result
    {
        try {
            $supplier = $this->findSupplierInScope($id);
            if ($supplier === null) {
                return $this->notFound();
            }

            $errors = [];
            if ((string) $supplier->status !== SupplierStatus::ACTIVE || ! (bool) $supplier->is_active) {
                $errors[] = 'Supplier must be active.';
            }

            $hasActiveAddress = $this->addresses->newQuery()
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->where('supplier_id', (int) $supplier->id)
                ->where('is_active', true)
                ->exists();

            if (! $hasActiveAddress) {
                $errors[] = 'Supplier requires at least one active address.';
            }

            $hasActiveContact = $this->contacts->newQuery()
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->where('supplier_id', (int) $supplier->id)
                ->where('is_active', true)
                ->exists();

            if (! $hasActiveContact) {
                $errors[] = 'Supplier requires at least one active contact.';
            }

            return Result::success([
                'supplier_id' => (int) $supplier->id,
                'context' => $this->normalizeContext($context),
                'is_valid' => $errors === [],
                'errors' => $errors,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function getFinanceDefaults(int|string $id): Result
    {
        try {
            $supplier = $this->findSupplierInScope($id);
            if ($supplier === null) {
                return $this->notFound();
            }

            return Result::success([
                'supplier_id' => (int) $supplier->id,
                'default_currency_id' => $supplier->default_currency_id,
                'default_payment_term_id' => $supplier->default_payment_term_id,
                'default_payable_account_id' => $supplier->default_payable_account_id,
                'default_expense_account_id' => $supplier->default_expense_account_id,
                'credit_limit' => $supplier->credit_limit,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function updateFinanceDefaults(int|string $id, array $payload): Result
    {
        try {
            $supplier = $this->findSupplierInScope($id);
            if ($supplier === null) {
                return $this->notFound();
            }

            $supplier->fill([
                'default_currency_id' => $this->toNullableInt($payload['default_currency_id'] ?? null),
                'default_payment_term_id' => $this->toNullableInt($payload['default_payment_term_id'] ?? null),
                'default_payable_account_id' => $this->toNullableInt($payload['default_payable_account_id'] ?? null),
                'default_expense_account_id' => $this->toNullableInt($payload['default_expense_account_id'] ?? null),
                'credit_limit' => $payload['credit_limit'] ?? null,
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => ((int) $supplier->row_version) + 1,
            ]);
            $supplier->save();

            return $this->getFinanceDefaults((int) $supplier->id);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function listSupplierUserAccounts(int|string $supplierId): Result
    {
        try {
            $supplier = $this->findSupplierInScope($supplierId);
            if ($supplier === null) {
                return $this->notFound();
            }

            $accounts = $this->userAccounts->newQuery()
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->where('supplier_id', (int) $supplier->id)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->get();

            return Result::success($accounts->map(fn ($item): array => $this->recordToArray($item))->all());
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function createSupplierUserAccess(int|string $supplierId, array $payload): Result
    {
        try {
            return DB::transaction(function () use ($supplierId, $payload): Result {
                $supplier = $this->findSupplierInScope($supplierId);
                if ($supplier === null) {
                    return $this->notFound();
                }

                $tenantId = (int) $supplier->tenant_id;
                $organizationUnitId = $this->toNullableInt(
                    $supplier->organization_unit_id,
                ) ?? $this->organizationUnitId();

                $userPayload = (array) ($payload['user'] ?? []);
                $email = $this->normalizeRequired((string) ($userPayload['email'] ?? ''), 'User email');
                $fullName = $this->normalizeRequired(
                    (string) ($userPayload['name'] ?? $supplier->supplier_name),
                    'User name',
                );
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
                        'created_from' => 'supplier_module',
                        'supplier_id' => (int) $supplier->id,
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
                        SupplierErrorCode::INVALID_VALUE,
                        'User creation did not return an identifier.',
                    ));
                }

                return $this->linkExistingUser((int) $supplier->id, [
                    'user_id' => $createdUserId,
                    'access_type' => $payload['access_type'] ?? 'portal',
                    'is_primary' => $payload['is_primary'] ?? false,
                    'metadata' => $payload['metadata'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function linkExistingUser(int|string $supplierId, array $payload): Result
    {
        try {
            $supplier = $this->findSupplierInScope($supplierId);
            if ($supplier === null) {
                return $this->notFound();
            }

            $userId = $this->toNullableInt($payload['user_id'] ?? null);
            if ($userId === null) {
                return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, 'User id is required for linking.'));
            }

            $tenantId = (int) $supplier->tenant_id;
            $exists = $this->userAccounts->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('supplier_id', (int) $supplier->id)
                ->where('user_id', $userId)
                ->exists();
            if ($exists) {
                return Result::failure(new Error(
                    SupplierErrorCode::INVALID_VALUE,
                    'User is already linked to supplier.',
                ));
            }

            $isPrimary = (bool) ($payload['is_primary'] ?? false);
            if ($isPrimary) {
                $this->userAccounts->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('supplier_id', (int) $supplier->id)
                    ->update(['is_primary' => false, 'updated_at' => now()]);
            }

            $link = $this->userAccounts->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt(
                    $supplier->organization_unit_id,
                ) ?? $this->organizationUnitId(),
                'metadata' => $this->normalizeArray($payload['metadata'] ?? null),
                'supplier_id' => (int) $supplier->id,
                'user_id' => $userId,
                'access_type' => $this->normalizeNullable($payload['access_type'] ?? null) ?? 'portal',
                'status' => 'active',
                'is_primary' => $isPrimary,
                'linked_at' => now(),
                'linked_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);

            return Result::success(new DataRecord($link->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function deactivateSupplierUserAccess(int|string $supplierId, int|string $accessId, array $payload): Result
    {
        try {
            $supplier = $this->findSupplierInScope($supplierId);
            if ($supplier === null) {
                return $this->notFound();
            }

            $access = $this->userAccounts->newQuery()
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->where('supplier_id', (int) $supplier->id)
                ->where('id', (int) $accessId)
                ->first();

            if ($access === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'Supplier user access not found.'));
            }

            $metadata = $this->normalizeArray($access->metadata ?? null) ?? [];
            if (isset($payload['reason']) && is_string($payload['reason']) && trim($payload['reason']) !== '') {
                $metadata['deactivation_reason'] = trim($payload['reason']);
            }

            $access->fill([
                'status' => 'inactive',
                'is_primary' => false,
                'deactivated_at' => now(),
                'deactivated_by' => $this->currentUser->currentUserId(),
                'metadata' => $metadata,
                'row_version' => ((int) $access->row_version) + 1,
            ]);
            $access->save();

            return Result::success(new DataRecord($access->toArray()));
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function unlinkSupplierUserAccess(int|string $supplierId, int|string $accessId): Result
    {
        try {
            $supplier = $this->findSupplierInScope($supplierId);
            if ($supplier === null) {
                return $this->notFound();
            }

            $access = $this->userAccounts->newQuery()
                ->where('tenant_id', (int) $supplier->tenant_id)
                ->where('supplier_id', (int) $supplier->id)
                ->where('id', (int) $accessId)
                ->first();

            if ($access === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'Supplier user access not found.'));
            }

            $access->delete();

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    private function supplierPayload(SupplierModel $supplier): DataRecord
    {
        $supplierId = (int) $supplier->id;
        $tenantId = (int) $supplier->tenant_id;

        $payload = $supplier->toArray();
        $payload['contacts'] = $this->contacts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();
        $payload['addresses'] = $this->addresses->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();
        $payload['bank_accounts'] = $this->bankAccounts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();
        $payload['tax_profile'] = $this->taxProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->first()?->toArray();
        $payload['user_accounts'] = $this->userAccounts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => $this->recordToArray($item))
            ->all();

        return new DataRecord($payload);
    }

    private function findSupplierInScope(int|string $id): ?SupplierModel
    {
        return $this->suppliers->newQuery()
            ->where('tenant_id', $this->tenantId())
            ->where('id', (int) $id)
            ->first();
    }

    private function replaceContacts(int $supplierId, mixed $contacts, int $tenantId, ?int $organizationUnitId): void
    {
        if (! is_array($contacts)) {
            return;
        }

        $this->contacts->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
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
                'supplier_id' => $supplierId,
                'name' => $this->normalizeRequired((string) ($entry['name'] ?? ''), 'Contact name'),
                'designation' => $this->normalizeNullable($entry['designation'] ?? null),
                'department' => $this->normalizeNullable($entry['department'] ?? null),
                'email' => $this->normalizeNullable($entry['email'] ?? null),
                'phone' => $this->normalizeNullable($entry['phone'] ?? null),
                'mobile' => $this->normalizeNullable($entry['mobile'] ?? null),
                'whatsapp' => $this->normalizeNullable($entry['whatsapp'] ?? null),
                'is_billing_contact' => (bool) ($entry['is_billing_contact'] ?? false),
                'is_procurement_contact' => (bool) ($entry['is_procurement_contact'] ?? false),
                'is_primary' => $isPrimary,
                'is_active' => (bool) ($entry['is_active'] ?? true),
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);
        }

        if (! $hasPrimary) {
            $first = $this->contacts->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('supplier_id', $supplierId)
                ->first();
            if ($first !== null) {
                $first->is_primary = true;
                $first->save();
            }
        }
    }

    private function replaceAddresses(int $supplierId, mixed $addresses, int $tenantId, ?int $organizationUnitId): void
    {
        if (! is_array($addresses)) {
            return;
        }

        $this->addresses->newQuery()->where('tenant_id', $tenantId)->where('supplier_id', $supplierId)->delete();

        foreach ($addresses as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $this->addresses->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->normalizeArray($entry['metadata'] ?? null),
                'supplier_id' => $supplierId,
                'type' => $this->normalizeNullable($entry['type'] ?? null) ?? 'billing',
                'label' => $this->normalizeNullable($entry['label'] ?? null),
                'contact_person' => $this->normalizeNullable($entry['contact_person'] ?? null),
                'contact_phone' => $this->normalizeNullable($entry['contact_phone'] ?? null),
                'address_line1' => $this->normalizeRequired((string) ($entry['address_line1'] ?? ''), 'Address line 1'),
                'address_line2' => $this->normalizeNullable($entry['address_line2'] ?? null),
                'city' => $this->normalizeRequired((string) ($entry['city'] ?? ''), 'City'),
                'state' => $this->normalizeNullable($entry['state'] ?? null),
                'postal_code' => $this->normalizeRequired((string) ($entry['postal_code'] ?? ''), 'Postal code'),
                'country_id' => $this->toNullableInt($entry['country_id'] ?? null),
                'is_default' => (bool) ($entry['is_default'] ?? false),
                'is_default_billing' => (bool) ($entry['is_default_billing'] ?? false),
                'is_default_shipping' => (bool) ($entry['is_default_shipping'] ?? false),
                'is_active' => (bool) ($entry['is_active'] ?? true),
                'geo_lat' => $entry['geo_lat'] ?? null,
                'geo_lng' => $entry['geo_lng'] ?? null,
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);
        }
    }

    private function replaceBankAccounts(
        int $supplierId,
        mixed $bankAccounts,
        int $tenantId,
        ?int $organizationUnitId,
    ): void {
        if (! is_array($bankAccounts)) {
            return;
        }

        $this->bankAccounts->newQuery()->where('tenant_id', $tenantId)->where('supplier_id', $supplierId)->delete();

        $hasPrimary = false;
        foreach ($bankAccounts as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $isPrimary = (bool) ($entry['is_primary'] ?? false);
            if ($isPrimary) {
                $hasPrimary = true;
            }

            $this->bankAccounts->newQuery()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->normalizeArray($entry['metadata'] ?? null),
                'supplier_id' => $supplierId,
                'account_name' => $this->normalizeRequired((string) ($entry['account_name'] ?? ''), 'Account name'),
                'account_number' => $this->normalizeRequired(
                    (string) ($entry['account_number'] ?? ''),
                    'Account number',
                ),
                'iban' => $this->normalizeNullable($entry['iban'] ?? null),
                'swift_code' => $this->normalizeNullable($entry['swift_code'] ?? null),
                'bank_name' => $this->normalizeRequired((string) ($entry['bank_name'] ?? ''), 'Bank name'),
                'branch_name' => $this->normalizeNullable($entry['branch_name'] ?? null),
                'bank_code' => $this->normalizeNullable($entry['bank_code'] ?? null),
                'branch_code' => $this->normalizeNullable($entry['branch_code'] ?? null),
                'currency_id' => $this->toNullableInt($entry['currency_id'] ?? null),
                'is_primary' => $isPrimary,
                'is_active' => (bool) ($entry['is_active'] ?? true),
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);
        }

        if (! $hasPrimary) {
            $first = $this->bankAccounts->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('supplier_id', $supplierId)
                ->first();
            if ($first !== null) {
                $first->is_primary = true;
                $first->save();
            }
        }
    }

    private function upsertTaxProfile(int $supplierId, mixed $taxProfile, int $tenantId, ?int $organizationUnitId): void
    {
        if ($taxProfile === null) {
            return;
        }

        if (! is_array($taxProfile)) {
            return;
        }

        $existing = $this->taxProfiles->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->first();

        $attributes = [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->normalizeArray($taxProfile['metadata'] ?? null),
            'tax_identifier' => $this->normalizeNullable($taxProfile['tax_identifier'] ?? null),
            'vat_identifier' => $this->normalizeNullable($taxProfile['vat_identifier'] ?? null),
            'tax_type' => $this->normalizeNullable($taxProfile['tax_type'] ?? null),
            'withholding_rate' => $taxProfile['withholding_rate'] ?? null,
            'is_tax_exempt' => (bool) ($taxProfile['is_tax_exempt'] ?? false),
            'tax_exempt_until' => $taxProfile['tax_exempt_until'] ?? null,
            'is_active' => (bool) ($taxProfile['is_active'] ?? true),
            'updated_by' => $this->currentUser->currentUserId(),
        ];

        if ($existing === null) {
            $this->taxProfiles->newQuery()->create(array_merge($attributes, [
                'tenant_id' => $tenantId,
                'supplier_id' => $supplierId,
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
        int $supplierId,
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
            'supplier_id' => $supplierId,
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
            throw new \RuntimeException('Current tenant context is required.');
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
            $resolved = SupplierStatus::DRAFT;
        }
        if (! in_array($resolved, SupplierStatus::values(), true)) {
            throw new \InvalidArgumentException('Invalid supplier status value.');
        }

        return $resolved;
    }

    private function normalizeRequired(string $value, string $label): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new \InvalidArgumentException($label.' is required.');
        }

        return $normalized;
    }

    private function normalizeCode(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            throw new \InvalidArgumentException('Supplier code is required.');
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
        return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'Supplier not found.'));
    }

    private function failure(string $message): Result
    {
        return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $message));
    }
}
