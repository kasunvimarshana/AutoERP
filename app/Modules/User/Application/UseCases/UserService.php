<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use InvalidArgumentException;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\PasswordHasherInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Domain\Constants\UserStatus;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserService extends AbstractUserCrudService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserTenantRepositoryInterface $userTenants,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly UserDomainServiceInterface $domain,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $perPage = max(1, (int) ($filters['per_page'] ?? 15));
            $page = max(1, (int) ($filters['page'] ?? 1));
            $tenantId = $this->resolveTenantId($this->toNullableInt($filters['tenant_id'] ?? null));
            $search = $this->domain->normalizeNullableString((string) ($filters['search'] ?? ''));

            return $this->success($this->users->pageByFilters($tenantId, $search, $perPage, $page));
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.list');
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $tenantId = $this->resolveTenantId(null);
            $record = $this->users->findById($id);
            if ($record === null || (int) $record->require('tenant_id') !== $tenantId) {
                return $this->notFound('User not found.');
            }

            return $this->success($record);
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.get', ['user_id' => (string) $id]);
        }
    }

    public function create(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->resolveTenantId($this->toNullableInt($payload['tenant_id'] ?? null));
                $email = $this->domain->normalizeEmail((string) ($payload['email'] ?? ''));
                $firstName = $this->domain->normalizeRequiredString(
                    (string) ($payload['first_name'] ?? ''),
                    'First name',
                );

                if ($this->users->findByTenantAndEmail($tenantId, $email) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_EMAIL, 'User email already exists in tenant scope.');
                }

                $organizationUnitId = $this->resolveOrganizationUnitId(
                    $tenantId,
                    $this->toNullableInt($payload['organization_unit_id'] ?? null),
                );

                $metadata = $this->domain->normalizeMetadata($payload['metadata'] ?? null);
                $metadata = $this->mergeIdentityReferences($metadata, $payload['identity_references'] ?? null);

                $created = $this->users->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $metadata,
                    'first_name' => $firstName,
                    'last_name' => $this->domain->normalizeNullableString($payload['last_name'] ?? null),
                    'email' => $email,
                    'email_verified_at' => $this->domain->normalizeNullableString(
                        $payload['email_verified_at'] ?? null,
                    ),
                    'password' => $this->passwordHasher->hash((string) ($payload['password'] ?? '')),
                    'status' => $this->domain->normalizeStatus($payload['status'] ?? null),
                    'avatar_path' => $this->domain->normalizeNullableString($payload['avatar_path'] ?? null),
                    'phone' => $this->domain->normalizeNullableString($payload['phone'] ?? null),
                    'preferences' => $this->domain->normalizeMetadata($payload['preferences'] ?? null),
                    'date_of_birth' => $this->domain->normalizeNullableString($payload['date_of_birth'] ?? null),
                    'gender' => $this->domain->normalizeNullableString($payload['gender'] ?? null),
                    'marital_status' => $this->domain->normalizeNullableString($payload['marital_status'] ?? null),
                    'row_version' => 1,
                ]);

                return $this->success($created);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.create');
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->users->findById($id);
                if ($existing === null || ! $this->isInTenantScope($existing)) {
                    return $this->notFound('User not found.');
                }

                $targetId = (int) $existing->id();
                $tenantId = (int) $existing->require('tenant_id');

                if (
                    array_key_exists('tenant_id', $payload)
                    && $this->toNullableInt($payload['tenant_id']) !== $tenantId
                ) {
                    return $this->failure(UserErrorCode::TENANT_MISMATCH, 'User tenant cannot be changed.');
                }

                $email = array_key_exists('email', $payload)
                    ? $this->domain->normalizeEmail((string) $payload['email'])
                    : (string) $existing->get('email');
                $firstName = array_key_exists('first_name', $payload)
                    ? $this->domain->normalizeRequiredString((string) $payload['first_name'], 'First name')
                    : (string) $existing->get('first_name');

                if ($this->users->findByTenantAndEmail($tenantId, $email, $targetId) !== null) {
                    return $this->failure(UserErrorCode::DUPLICATE_EMAIL, 'User email already exists in tenant scope.');
                }

                $organizationUnitId = array_key_exists('organization_unit_id', $payload)
                    ? $this->toNullableInt($payload['organization_unit_id'])
                    : $this->toNullableInt($existing->get('organization_unit_id'));
                $organizationUnitId = $this->resolveOrganizationUnitId($tenantId, $organizationUnitId);

                $metadata = array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata'));
                $metadata = $this->mergeIdentityReferences($metadata, $payload['identity_references'] ?? null);

                $updated = $this->users->update($id, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $metadata,
                    'first_name' => $firstName,
                    'last_name' => array_key_exists('last_name', $payload)
                        ? $this->domain->normalizeNullableString($payload['last_name'])
                        : $existing->get('last_name'),
                    'email' => $email,
                    'email_verified_at' => array_key_exists('email_verified_at', $payload)
                        ? $this->domain->normalizeNullableString($payload['email_verified_at'])
                        : $existing->get('email_verified_at'),
                    'password' => array_key_exists('password', $payload)
                        ? $this->passwordHasher->hash((string) $payload['password'])
                        : $existing->get('password'),
                    'status' => array_key_exists('status', $payload)
                        ? $this->domain->normalizeStatus($payload['status'])
                        : $existing->get('status'),
                    'avatar_path' => array_key_exists('avatar_path', $payload)
                        ? $this->domain->normalizeNullableString($payload['avatar_path'])
                        : $existing->get('avatar_path'),
                    'phone' => array_key_exists('phone', $payload)
                        ? $this->domain->normalizeNullableString($payload['phone'])
                        : $existing->get('phone'),
                    'preferences' => array_key_exists('preferences', $payload)
                        ? $this->domain->normalizeMetadata($payload['preferences'])
                        : $existing->get('preferences'),
                    'date_of_birth' => array_key_exists('date_of_birth', $payload)
                        ? $this->domain->normalizeNullableString($payload['date_of_birth'])
                        : $existing->get('date_of_birth'),
                    'gender' => array_key_exists('gender', $payload)
                        ? $this->domain->normalizeNullableString($payload['gender'])
                        : $existing->get('gender'),
                    'marital_status' => array_key_exists('marital_status', $payload)
                        ? $this->domain->normalizeNullableString($payload['marital_status'])
                        : $existing->get('marital_status'),
                    'row_version' => (int) $existing->get('row_version', 1) + 1,
                ]);

                return $this->success($updated);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.update', ['user_id' => (string) $id]);
        }
    }

    public function activate(int|string $id): Result
    {
        return $this->setStatus($id, UserStatus::ACTIVE);
    }

    public function deactivate(int|string $id): Result
    {
        return $this->setStatus($id, UserStatus::INACTIVE);
    }

    public function suspend(int|string $id): Result
    {
        return $this->setStatus($id, UserStatus::SUSPENDED);
    }

    public function assignUserToOrganizationUnit(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $user = $this->users->findById($id);
                if ($user === null || ! $this->isInTenantScope($user)) {
                    return $this->notFound('User not found.');
                }

                $tenantId = (int) $user->require('tenant_id');
                $organizationUnitId = $this->resolveOrganizationUnitId(
                    $tenantId,
                    $this->toNullableInt($payload['organization_unit_id'] ?? null),
                );
                if ($organizationUnitId === null) {
                    return $this->failure(
                        UserErrorCode::ORGANIZATION_UNIT_NOT_FOUND,
                        'Organization unit identifier is required.',
                    );
                }

                $existingAssignment = $this->userTenants->findByTenantOrganizationUser(
                    $tenantId,
                    $organizationUnitId,
                    (int) $user->id(),
                );
                if ($existingAssignment !== null) {
                    return $this->failure(
                        UserErrorCode::DUPLICATE_USER_TENANT,
                        'User is already assigned to this organization unit.',
                    );
                }

                $isDefault = $this->toBool($payload['is_default'] ?? false);
                if ($isDefault) {
                    $this->userTenants->clearDefaultForUser($tenantId, (int) $user->id());
                }

                $assignment = $this->userTenants->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'user_id' => (int) $user->id(),
                    'role_id' => $this->toNullableInt($payload['role_id'] ?? null),
                    'is_default' => $isDefault,
                    'row_version' => 1,
                ]);

                if ($isDefault || $user->get('organization_unit_id') === null) {
                    $this->users->update($id, [
                        'organization_unit_id' => $organizationUnitId,
                        'row_version' => (int) $user->get('row_version', 1) + 1,
                    ]);
                }

                return $this->success($assignment);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.assign-organization', ['user_id' => (string) $id]);
        }
    }

    public function removeUserFromOrganizationUnit(int|string $id, int|string $organizationUnitId): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $organizationUnitId): Result {
                $user = $this->users->findById($id);
                if ($user === null || ! $this->isInTenantScope($user)) {
                    return $this->notFound('User not found.');
                }

                $tenantId = (int) $user->require('tenant_id');
                $orgId = $this->toNullableInt($organizationUnitId);
                if ($orgId === null) {
                    return $this->failure(
                        UserErrorCode::ORGANIZATION_UNIT_NOT_FOUND,
                        'Organization unit identifier is invalid.',
                    );
                }

                $assignment = $this->userTenants->findByTenantOrganizationUser($tenantId, $orgId, (int) $user->id());
                if ($assignment === null) {
                    return $this->failure(UserErrorCode::ASSIGNMENT_NOT_FOUND, 'Organization assignment not found.');
                }

                $this->userTenants->delete($assignment->id());

                if ($this->toNullableInt($user->get('organization_unit_id')) === $orgId) {
                    $this->users->update($id, [
                        'organization_unit_id' => null,
                        'row_version' => (int) $user->get('row_version', 1) + 1,
                    ]);
                }

                return $this->success(true);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.remove-organization', [
                'user_id' => (string) $id,
                'organization_unit_id' => (string) $organizationUnitId,
            ]);
        }
    }

    public function resolveByIdentity(string $providerKey, string $providerUserKey): Result
    {
        try {
            $tenantId = $this->resolveTenantId(null);
            $normalizedProviderKey = trim(strtolower($providerKey));
            $normalizedProviderUserKey = trim($providerUserKey);
            if ($normalizedProviderKey === '' || $normalizedProviderUserKey === '') {
                return $this->failure(
                    UserErrorCode::IDENTITY_REFERENCE_INVALID,
                    'Identity provider key and user key are required.',
                );
            }

            $record = $this->users->findByTenantAndIdentityReference(
                $tenantId,
                $normalizedProviderKey,
                $normalizedProviderUserKey,
            );
            if ($record === null) {
                return $this->notFound('User not found for given identity reference.');
            }

            return $this->success($record);
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.resolve-identity', [
                'provider_key' => $providerKey,
            ]);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->users->findById($id);
                if ($existing === null || ! $this->isInTenantScope($existing)) {
                    return $this->notFound('User not found.');
                }

                if (! $this->users->delete($id)) {
                    return $this->notFound('User not found.');
                }

                return $this->success(true);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.delete', ['user_id' => (string) $id]);
        }
    }

    private function setStatus(int|string $id, string $status): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $status): Result {
                $existing = $this->users->findById($id);
                if ($existing === null || ! $this->isInTenantScope($existing)) {
                    return $this->notFound('User not found.');
                }

                $record = $this->users->update($id, [
                    'status' => $status,
                    'row_version' => (int) $existing->get('row_version', 1) + 1,
                ]);

                return $this->success($record);
            });
        } catch (Throwable $exception) {
            return $this->normalizeFailure($exception, 'user.status', [
                'user_id' => (string) $id,
                'status' => $status,
            ]);
        }
    }

    private function isInTenantScope(DataRecord $record): bool
    {
        $tenantId = $this->resolveTenantId(null);

        return (int) $record->require('tenant_id') === $tenantId;
    }

    private function resolveTenantId(?int $requestedTenantId): int
    {
        $currentTenantId = $this->currentTenant->currentTenantId();
        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            throw new InvalidArgumentException('Tenant scope mismatch for user operation.');
        }

        $resolvedTenantId = $requestedTenantId ?? $currentTenantId;
        if ($resolvedTenantId === null || $resolvedTenantId < 1) {
            throw new InvalidArgumentException('Tenant context is required for user operations.');
        }

        return $resolvedTenantId;
    }

    private function resolveOrganizationUnitId(int $tenantId, ?int $organizationUnitId): ?int
    {
        $contextOrganizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();
        $resolvedOrganizationUnitId = $organizationUnitId ?? $contextOrganizationUnitId;
        if ($resolvedOrganizationUnitId === null) {
            return null;
        }

        $record = $this->organizationUnits->findById($resolvedOrganizationUnitId);
        if ($record === null) {
            throw new InvalidArgumentException('Organization unit not found.');
        }

        if ((int) $record->require('tenant_id') !== $tenantId) {
            throw new InvalidArgumentException('Organization unit must belong to same tenant as user.');
        }

        return $resolvedOrganizationUnitId;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function mergeIdentityReferences(?array $metadata, mixed $identityReferences): ?array
    {
        if ($identityReferences === null) {
            return $metadata;
        }

        if (! is_array($identityReferences)) {
            throw new InvalidArgumentException('Identity references must be an object map.');
        }

        $normalizedReferences = [];
        foreach ($identityReferences as $providerKey => $providerUserKey) {
            if (! is_string($providerKey) || trim($providerKey) === '') {
                throw new InvalidArgumentException('Identity provider key must be a non-empty string.');
            }

            if (! is_scalar($providerUserKey) || trim((string) $providerUserKey) === '') {
                throw new InvalidArgumentException('Identity provider user key must be a non-empty scalar value.');
            }

            $normalizedReferences[strtolower(trim($providerKey))] = trim((string) $providerUserKey);
        }

        $base = $metadata ?? [];
        $existingReferences = $base['identity_references'] ?? [];
        if (! is_array($existingReferences)) {
            $existingReferences = [];
        }

        $base['identity_references'] = array_merge($existingReferences, $normalizedReferences);

        return $base;
    }

    /**
     * @param  array<string, scalar|array|null>  $context
     */
    private function normalizeFailure(Throwable $exception, string $operation, array $context = []): Result
    {
        return Result::failure($this->errorNormalizer->normalize(
            $exception,
            UserErrorCode::INVALID_VALUE,
            array_merge(['operation' => $operation], $context),
        ));
    }
}
