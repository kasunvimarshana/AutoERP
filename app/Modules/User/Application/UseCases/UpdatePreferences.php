<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdatePreferences
{
    public function __construct(
        private UserRepositoryInterface $userRepo
    ) {}

    public function execute(int $userId, UserPreferencesData $data): User
    {
        $user = $this->userRepo->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $preferences = new UserPreferences(
            $data->language ?? 'en',
            $data->timezone ?? 'UTC',
            $data->notifications ?? []
        );

        $user->updatePreferences($preferences);

        return $this->userRepo->save($user);
    }
}
