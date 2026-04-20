<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Supplier\Domain\Contracts\SupplierUserSynchronizerInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class EloquentSupplierUserSynchronizer implements SupplierUserSynchronizerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly FileStorageServiceInterface $fileStorageService,
    ) {}

    public function resolveUserIdForCreate(
        int $tenantId,
        ?int $orgUnitId,
        ?int $requestedUserId,
        ?array $userPayload,
    ): int {
        if ($requestedUserId !== null) {
            $user = $this->findTenantUserOrFail($tenantId, $requestedUserId);

            if ($userPayload !== null) {
                $this->applyUserUpdates($tenantId, $user, $orgUnitId, $userPayload);
            } elseif ($user->getOrgUnitId() !== $orgUnitId) {
                $this->userRepository->updateRecord(
                    $tenantId,
                    $requestedUserId,
                    ['org_unit_id' => $orgUnitId]
                );
            }

            return $requestedUserId;
        }

        if ($userPayload === null) {
            throw new DomainException('Either user_id or user payload is required to create a supplier.');
        }

        $email = trim((string) ($userPayload['email'] ?? ''));
        $firstName = trim((string) ($userPayload['first_name'] ?? ''));
        $lastName = trim((string) ($userPayload['last_name'] ?? ''));

        if ($email === '' || $firstName === '' || $lastName === '') {
            throw new DomainException('User payload must include email, first_name, and last_name.');
        }

        $existingByEmail = $this->userRepository->findByEmail($tenantId, $email);
        if ($existingByEmail !== null) {
            throw new DomainException('A user with this email already exists in the tenant.');
        }

        $status = array_key_exists('active', $userPayload)
            ? ((bool) $userPayload['active'] ? 'active' : 'inactive')
            : 'active';

        $createdUserId = $this->userRepository->createRecord([
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnitId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => isset($userPayload['phone']) && $userPayload['phone'] !== '' ? (string) $userPayload['phone'] : null,
            'address' => isset($userPayload['address']) && is_array($userPayload['address']) ? $userPayload['address'] : null,
            'preferences' => isset($userPayload['preferences']) && is_array($userPayload['preferences']) ? $userPayload['preferences'] : null,
            'status' => $status,
            'password' => Hash::make(Str::random(40)),
        ]);

        if (array_key_exists('avatar', $userPayload)) {
            $avatar = $userPayload['avatar'];

            if ($avatar instanceof UploadedFile) {
                $storedAvatarPath = null;

                try {
                    $storedAvatarPath = $this->fileStorageService->storeFile(
                        $avatar,
                        'avatars/'.$createdUserId,
                        $avatar->getClientOriginalName()
                    );

                    $this->userRepository->updateRecord($tenantId, $createdUserId, ['avatar' => $storedAvatarPath]);
                } catch (\Throwable $exception) {
                    if (is_string($storedAvatarPath) && $storedAvatarPath !== '') {
                        $this->fileStorageService->delete($storedAvatarPath);
                    }

                    throw $exception;
                }
            } elseif ($avatar !== null && $avatar !== '') {
                throw new DomainException('User avatar must be a valid uploaded file.');
            }
        }

        return $createdUserId;
    }

    public function synchronizeForSupplierUpdate(
        int $tenantId,
        int $userId,
        ?int $orgUnitId,
        ?array $userPayload,
    ): void {
        $user = $this->findTenantUserOrFail($tenantId, $userId);

        if ($userPayload === null) {
            if ($user->getOrgUnitId() !== $orgUnitId) {
                $this->userRepository->updateRecord($tenantId, $userId, ['org_unit_id' => $orgUnitId]);
            }

            return;
        }

        $this->applyUserUpdates($tenantId, $user, $orgUnitId, $userPayload);
    }

    private function findTenantUserOrFail(int $tenantId, int $userId): User
    {
        $user = $this->userRepository->findByTenantAndId($tenantId, $userId);

        if ($user === null) {
            throw new DomainException('The selected user does not belong to the tenant.');
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $userPayload
     */
    private function applyUserUpdates(int $tenantId, User $user, ?int $orgUnitId, array $userPayload): void
    {
        $previousAvatarPath = $user->getAvatar();
        /** @var array<string, mixed> $attributes */
        $attributes = [];

        if (array_key_exists('email', $userPayload)) {
            $email = trim((string) $userPayload['email']);
            if ($email === '') {
                throw new DomainException('User email cannot be empty.');
            }

            $existingByEmail = $this->userRepository->findByEmail($tenantId, $email);
            if ($existingByEmail !== null && $existingByEmail->getId() !== $user->getId()) {
                throw new DomainException('A user with this email already exists in the tenant.');
            }

            $attributes['email'] = $email;
        }

        if (array_key_exists('first_name', $userPayload)) {
            $firstName = trim((string) $userPayload['first_name']);
            if ($firstName === '') {
                throw new DomainException('User first_name cannot be empty.');
            }
            $attributes['first_name'] = $firstName;
        }

        if (array_key_exists('last_name', $userPayload)) {
            $lastName = trim((string) $userPayload['last_name']);
            if ($lastName === '') {
                throw new DomainException('User last_name cannot be empty.');
            }
            $attributes['last_name'] = $lastName;
        }

        if (array_key_exists('phone', $userPayload)) {
            $attributes['phone'] = $userPayload['phone'] !== null && $userPayload['phone'] !== ''
                ? (string) $userPayload['phone']
                : null;
        }

        if (array_key_exists('address', $userPayload)) {
            $attributes['address'] = is_array($userPayload['address']) ? $userPayload['address'] : null;
        }

        if (array_key_exists('preferences', $userPayload)) {
            $attributes['preferences'] = is_array($userPayload['preferences']) ? $userPayload['preferences'] : null;
        }

        if (array_key_exists('active', $userPayload)) {
            $attributes['status'] = (bool) $userPayload['active'] ? 'active' : 'inactive';
        }

        $storedAvatarPath = null;

        if (array_key_exists('avatar', $userPayload)) {
            $avatar = $userPayload['avatar'];

            if ($avatar instanceof UploadedFile) {
                $storedAvatarPath = $this->fileStorageService->storeFile(
                    $avatar,
                    'avatars/'.(string) $user->getId(),
                    $avatar->getClientOriginalName()
                );
                $attributes['avatar'] = $storedAvatarPath;
            } elseif ($avatar === null || $avatar === '') {
                $attributes['avatar'] = null;
            } else {
                throw new DomainException('User avatar must be a valid uploaded file.');
            }
        }

        $attributes['org_unit_id'] = $orgUnitId;

        try {
            $this->userRepository->updateRecord($tenantId, (int) $user->getId(), $attributes);

            $currentAvatarPath = array_key_exists('avatar', $attributes)
                ? (is_string($attributes['avatar']) && $attributes['avatar'] !== '' ? $attributes['avatar'] : null)
                : $previousAvatarPath;

            if ($previousAvatarPath !== null && $previousAvatarPath !== $currentAvatarPath) {
                DB::afterCommit(fn (): bool => $this->fileStorageService->delete($previousAvatarPath));
            }
        } catch (\Throwable $exception) {
            if (is_string($storedAvatarPath) && $storedAvatarPath !== '') {
                $this->fileStorageService->delete($storedAvatarPath);
            }

            throw $exception;
        }
    }
}
