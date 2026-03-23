<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\ValueObjects\Address;
use Modules\User\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Events\UserUpdated;

class UpdateUserService extends BaseService
{
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): User
    {
        $id = $data['id'];
        $dto = UserData::fromArray($data);

        $user = $this->repository->find($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $phone = $dto->phone ? new PhoneNumber($dto->phone) : null;
        $address = $dto->address ? Address::fromArray($dto->address) : null;
        $user->updateProfile($dto->first_name, $dto->last_name, $phone, $address);

        if ($dto->preferences !== null) {
            $preferences = new UserPreferences(
                $dto->preferences['language'] ?? $user->getPreferences()->getLanguage(),
                $dto->preferences['timezone'] ?? $user->getPreferences()->getTimezone(),
                $dto->preferences['notifications'] ?? $user->getPreferences()->getNotifications()
            );
            $user->updatePreferences($preferences);
        }

        if ($user->isActive() !== $dto->active) {
            $dto->active ? $user->activate() : $user->deactivate();
        }

        $saved = $this->repository->save($user);

        if (isset($dto->roles)) {
            $this->repository->syncRoles($saved, $dto->roles);
        }

        $this->addEvent(new UserUpdated($saved));
        return $saved;
    }
}
