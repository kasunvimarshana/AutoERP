<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Events\UserUpdated;

class UpdatePreferencesService extends BaseService
{
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): User
    {
        $userId = $data['user_id'];
        $dto = UserPreferencesData::fromArray($data);

        $user = $this->repository->find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $preferences = new UserPreferences(
            $dto->language ?? $user->getPreferences()->getLanguage(),
            $dto->timezone ?? $user->getPreferences()->getTimezone(),
            $dto->notifications ?? $user->getPreferences()->getNotifications()
        );
        $user->updatePreferences($preferences);
        $saved = $this->repository->save($user);
        $this->addEvent(new UserUpdated($saved));
        return $saved;
    }
}
