<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserProfileUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdateProfileService extends BaseService implements UpdateProfileServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): User
    {
        $userId = (int) $data['user_id'];
        $dto = UpdateProfileData::fromArray($data);

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $phone = ! empty($dto->phone) ? new PhoneNumber($dto->phone) : null;
        $address = ! empty($dto->address) ? Address::fromArray($dto->address) : null;
        $user->updateProfile($dto->first_name, $dto->last_name, $phone, $address);

        $saved = $this->userRepository->save($user);
        $this->addEvent(new UserProfileUpdated($saved));

        return $saved;
    }
}
