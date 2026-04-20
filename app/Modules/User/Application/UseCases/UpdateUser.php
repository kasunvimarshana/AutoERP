<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\Address;
use Modules\User\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\ValueObjects\UserPreferences;

class UpdateUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $id, UserData $data): User
    {
        $user = $this->userRepository->find($id);
        if (! $user) {
            throw new UserNotFoundException($id);
        }

        $phone = $data->phone ? new PhoneNumber($data->phone) : null;
        $address = $data->address ? Address::fromArray($data->address) : null;
        $preferences = $data->preferences
            ? new UserPreferences(
                $data->preferences['language'] ?? 'en',
                $data->preferences['timezone'] ?? 'UTC',
                $data->preferences['notifications'] ?? []
            )
            : null;

        $user->updateProfile($data->first_name, $data->last_name, $phone, $address);
        if ($preferences) {
            $user->updatePreferences($preferences);
        }
        if (isset($data->active)) {
            $data->active ? $user->activate() : $user->deactivate();
        }

        $saved = $this->userRepository->save($user);
        event(new UserUpdated($saved));

        return $saved;
    }
}
