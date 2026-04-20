<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\Address;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\ValueObjects\UserPreferences;

class CreateUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function execute(UserData $data): User
    {
        $email = new Email($data->email);
        $phone = $data->phone ? new PhoneNumber($data->phone) : null;
        $address = $data->address ? Address::fromArray($data->address) : null;
        $preferences = $data->preferences
            ? new UserPreferences(
                $data->preferences['language'] ?? 'en',
                $data->preferences['timezone'] ?? 'UTC',
                $data->preferences['notifications'] ?? []
            )
            : new UserPreferences;

        $user = new User(
            tenantId: $data->tenant_id,
            orgUnitId: $data->org_unit_id,
            email: $email,
            firstName: $data->first_name,
            lastName: $data->last_name,
            phone: $phone,
            address: $address,
            preferences: $preferences,
            active: $data->active ?? true,
            avatar: $data->avatar
        );

        $saved = $this->userRepository->save($user);

        if (! empty($data->roles)) {
            $roleIds = [];
            foreach ($data->roles as $roleId) {
                $role = $this->roleRepository->find((int) $roleId);
                if ($role) {
                    $saved->assignRole($role);
                    if ($role->getId() !== null) {
                        $roleIds[] = $role->getId();
                    }
                }
            }
            $this->userRepository->syncRoles($saved, $roleIds);
        }

        event(new UserCreated($saved));

        return $saved;
    }
}
