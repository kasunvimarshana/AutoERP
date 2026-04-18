<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdatePreferences
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $userId, UserPreferencesData $data): User
    {
        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $preferences = new UserPreferences(
            $data->language ?? 'en',
            $data->timezone ?? 'UTC',
            $data->notifications ?? []
        );

        $user->updatePreferences($preferences);

        return $this->userRepository->save($user);
    }
}
