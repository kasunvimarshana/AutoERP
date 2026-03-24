<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Events\UserUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;

class UpdatePreferencesService extends BaseService implements UpdatePreferencesServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    protected function handle(array $data): User
    {
        $userId = $data['user_id'];
        $dto = UserPreferencesData::fromArray($data);

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        $preferences = new UserPreferences(
            $dto->language ?? $user->getPreferences()->getLanguage(),
            $dto->timezone ?? $user->getPreferences()->getTimezone(),
            $dto->notifications ?? $user->getPreferences()->getNotifications()
        );
        $user->updatePreferences($preferences);
        $saved = $this->userRepository->save($user);
        $this->addEvent(new UserUpdated($saved));
        return $saved;
    }
}
