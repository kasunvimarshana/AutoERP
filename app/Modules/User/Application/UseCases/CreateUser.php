<?php

namespace Modules\User\Application\UseCases;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Events\UserCreated;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\UserPreferences;

class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private RoleRepositoryInterface $roleRepo
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
            : new UserPreferences();

        $user = new User(
            tenantId: $data->tenant_id,
            email: $email,
            firstName: $data->first_name,
            lastName: $data->last_name,
            phone: $phone,
            address: $address,
            preferences: $preferences,
            active: $data->active
        );

        $saved = $this->userRepo->save($user);

        if (!empty($data->roles)) {
            $roleIds = [];
            foreach ($data->roles as $roleId) {
                $role = $this->roleRepo->find($roleId);
                if ($role) {
                    $saved->assignRole($role);
                    $roleIds[] = $role->getId();
                }
            }
            $this->userRepo->syncRoles($saved, $roleIds);
        }

        event(new UserCreated($saved));

        return $saved;
    }
}
