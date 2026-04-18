<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\ValueObjects\Address;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\ValueObjects\UserPreferences;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdateUserService extends BaseService implements UpdateUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): User
    {
        $userId = (int) $data['id'];

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        if (array_key_exists('email', $data) && is_string($data['email']) && $data['email'] !== '') {
            $user->changeEmail(new Email($data['email']));
        }

        $firstName = array_key_exists('first_name', $data) ? (string) $data['first_name'] : $user->getFirstName();
        $lastName = array_key_exists('last_name', $data) ? (string) $data['last_name'] : $user->getLastName();

        $phone = array_key_exists('phone', $data)
            ? (! empty($data['phone']) ? new PhoneNumber((string) $data['phone']) : null)
            : $user->getPhone();

        $address = array_key_exists('address', $data)
            ? (! empty($data['address']) ? Address::fromArray($data['address']) : null)
            : $user->getAddress();

        $user->updateProfile($firstName, $lastName, $phone, $address);

        if (array_key_exists('preferences', $data) && is_array($data['preferences'])) {
            $preferences = new UserPreferences(
                $data['preferences']['language'] ?? $user->getPreferences()->getLanguage(),
                $data['preferences']['timezone'] ?? $user->getPreferences()->getTimezone(),
                $data['preferences']['notifications'] ?? $user->getPreferences()->getNotifications()
            );
            $user->updatePreferences($preferences);
        }

        if (array_key_exists('active', $data) && $user->isActive() !== (bool) $data['active']) {
            ((bool) $data['active']) ? $user->activate() : $user->deactivate();
        }

        if (array_key_exists('org_unit_id', $data)) {
            $orgUnitId = isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null;
            $user->changeOrgUnit($orgUnitId);
        }

        if (array_key_exists('avatar', $data)) {
            $avatarPath = is_string($data['avatar']) && $data['avatar'] !== ''
                ? $data['avatar']
                : null;
            $user->changeAvatar($avatarPath);
        }

        $saved = $this->userRepository->save($user);

        if (array_key_exists('roles', $data) && is_array($data['roles'])) {
            $roleIds = array_values(array_unique(array_filter(
                array_map('intval', $data['roles']),
                static fn (int $roleId): bool => $roleId > 0
            )));
            $this->userRepository->syncRoles($saved, $roleIds);
        }

        $this->addEvent(new UserUpdated($saved));

        return $saved;
    }
}
