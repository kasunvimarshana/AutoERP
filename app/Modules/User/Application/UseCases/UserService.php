<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Application\Results\Result;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use Modules\User\Application\Events\UserCreated;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserService extends AbstractUserCrudService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserDomainServiceInterface $domain,
    ) {
    }

    public function list(array $filters): Result
    {
        try {
            $perPage = max(1, (int) ($filters['per_page'] ?? 15));
            $page = max(1, (int) ($filters['page'] ?? 1));
            $tenantId = $this->toNullableInt($filters['tenant_id'] ?? null);
            $search = $this->domain->normalizeNullableString((string) ($filters['search'] ?? ''));

            return $this->success($this->users->pageByFilters($tenantId, $search, $perPage, $page));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->users->findById($id);
            if ($record === null) {
                return $this->notFound('User not found.');
            }

            return $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $email = $this->domain->normalizeEmail((string) ($payload['email'] ?? ''));
            if ($this->users->findByTenantAndEmail($tenantId, $email) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_EMAIL, 'User email already exists in tenant scope.');
            }

            $created = $this->users->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'first_name' => trim((string) ($payload['first_name'] ?? '')),
                'last_name' => $this->domain->normalizeNullableString($payload['last_name'] ?? null),
                'email' => $email,
                'email_verified_at' => $this->domain->normalizeNullableString($payload['email_verified_at'] ?? null),
                'password' => (string) ($payload['password'] ?? ''),
                'status' => $this->domain->normalizeStatus($payload['status'] ?? null),
                'avatar_path' => $this->domain->normalizeNullableString($payload['avatar_path'] ?? null),
                'phone' => $this->domain->normalizeNullableString($payload['phone'] ?? null),
                'preferences' => $this->domain->normalizeMetadata($payload['preferences'] ?? null),
                'date_of_birth' => $this->domain->normalizeNullableString($payload['date_of_birth'] ?? null),
                'gender' => $this->domain->normalizeNullableString($payload['gender'] ?? null),
                'marital_status' => $this->domain->normalizeNullableString($payload['marital_status'] ?? null),
                'row_version' => 1,
            ]);

            $this->dispatchEvent(new UserCreated($created->id(), $tenantId, $email));

            return $this->success($created);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->users->findById($id);
            if ($existing === null) {
                return $this->notFound('User not found.');
            }

            $targetId = (int) $existing->id();
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $email = array_key_exists('email', $payload)
                ? $this->domain->normalizeEmail((string) $payload['email'])
                : (string) $existing->get('email');

            if ($this->users->findByTenantAndEmail($tenantId, $email, $targetId) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_EMAIL, 'User email already exists in tenant scope.');
            }

            $updated = $this->users->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id')),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'first_name' => array_key_exists('first_name', $payload)
                    ? trim((string) $payload['first_name'])
                    : $existing->get('first_name'),
                'last_name' => array_key_exists('last_name', $payload)
                    ? $this->domain->normalizeNullableString($payload['last_name'])
                    : $existing->get('last_name'),
                'email' => $email,
                'email_verified_at' => array_key_exists('email_verified_at', $payload)
                    ? $this->domain->normalizeNullableString($payload['email_verified_at'])
                    : $existing->get('email_verified_at'),
                'password' => array_key_exists('password', $payload)
                    ? (string) $payload['password']
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
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->users->delete($id)) {
                return $this->notFound('User not found.');
            }

            return $this->success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function dispatchEvent(object $event): void
    {
        try {
            event($event);
        } catch (Throwable) {
            // Allows isolated unit tests without a booted Laravel dispatcher.
        }
    }
}
