<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserProfileUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdateProfileService implements UpdateProfileServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repo) {}

    public function execute(int $id, UpdateProfileData $data): User
    {
        $user = $this->repo->update($id, [
            'name' => $data->name,
            'phone' => $data->phone,
        ]);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        event(new UserProfileUpdated($user->getTenantId(), $user->getId()));
        return $user;
    }
}
