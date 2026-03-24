<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Events\UserUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;

class UpdateUserService extends BaseService implements UpdateUserServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    protected function handle(array $data): User
    {
        $id = $data['id'];
        $dto = UserData::fromArray($data);

        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }

        $phone = !empty($dto->phone) ? new PhoneNumber($dto->phone) : null;
        $address = !empty($dto->address) ? Address::fromArray($dto->address) : null;
        $user->updateProfile($dto->first_name ?? $user->getFirstName(), $dto->last_name ?? $user->getLastName(), $phone, $address);

        if ($dto->preferences !== null) {
            $preferences = new UserPreferences(
                $dto->preferences['language'] ?? $user->getPreferences()->getLanguage(),
                $dto->preferences['timezone'] ?? $user->getPreferences()->getTimezone(),
                $dto->preferences['notifications'] ?? $user->getPreferences()->getNotifications()
            );
            $user->updatePreferences($preferences);
        }

        if (isset($dto->active) && $user->isActive() !== $dto->active) {
            $dto->active ? $user->activate() : $user->deactivate();
        }

        $saved = $this->userRepository->save($user);

        if (isset($dto->roles)) {
            $this->userRepository->syncRoles($saved, $dto->roles);
        }

        $this->addEvent(new UserUpdated($saved));
        return $saved;
    }
}
