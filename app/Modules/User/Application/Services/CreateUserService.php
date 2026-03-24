<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class CreateUserService extends BaseService implements CreateUserServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $repository,
        protected RoleRepositoryInterface $roleRepo
    ) {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    protected function handle(array $data): User
    {
        $dto = UserData::fromArray($data);

        $email = new Email($dto->email);
        $phone = $dto->phone ? new PhoneNumber($dto->phone) : null;
        $address = $dto->address ? Address::fromArray($dto->address) : null;
        $preferences = $dto->preferences ? new UserPreferences(
            $dto->preferences['language'] ?? 'en',
            $dto->preferences['timezone'] ?? 'UTC',
            $dto->preferences['notifications'] ?? []
        ) : new UserPreferences;

        $user = new User(
            tenantId: $dto->tenant_id,
            email: $email,
            firstName: $dto->first_name,
            lastName: $dto->last_name,
            phone: $phone,
            address: $address,
            preferences: $preferences,
            active: $dto->active
        );

        $saved = $this->userRepository->save($user);

        if (! empty($dto->roles)) {
            $roleIds = [];
            foreach ($dto->roles as $roleId) {
                $role = $this->roleRepo->find($roleId);
                if ($role) {
                    $saved->assignRole($role);
                    $roleIds[] = $role->getId();
                }
            }
            $this->userRepository->syncRoles($saved, $roleIds);
        }

        $this->addEvent(new UserCreated($saved));

        return $saved;
    }
}
