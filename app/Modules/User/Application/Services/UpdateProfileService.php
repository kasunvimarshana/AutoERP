<?php
namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserProfileUpdated;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdateProfileService implements UpdateProfileServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repository) {}

    public function execute(User $user, UpdateProfileData $data): User
    {
        $payload = array_filter($data->toArray(), fn($v) => $v !== null);
        $updated = $this->repository->update($user, $payload);
        Event::dispatch(new UserProfileUpdated($user->tenantId, $user->id));
        return $updated;
    }
}
