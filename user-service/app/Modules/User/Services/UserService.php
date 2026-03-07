<?php

namespace App\Modules\User\Services;

use App\Modules\User\Services\Contracts\UserServiceInterface;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\User\DTOs\UserDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class UserService implements UserServiceInterface
{
    private UserRepositoryInterface $userRepository;
    private WebhookService $webhookService; // Injecting webhook integrations

    public function __construct(
        UserRepositoryInterface $userRepository,
        WebhookService $webhookService
    ) {
        $this->userRepository = $userRepository;
        $this->webhookService = $webhookService;
    }

    public function getAllUsers(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->getAllWithFilters($filters);
    }

    public function getUserById(int $id)
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data)
    {
        $userDTO = UserDTO::fromArray($data);

        DB::beginTransaction();

        try {
            $user = $this->userRepository->create($userDTO->toArray());

            // Dispatch domain event internally (RabbitMQ listener will pick this up)
            event(new \App\Modules\User\Events\UserCreated($user));

            // Execute Webhook Notification
            $this->webhookService->dispatch('user.created', $user->toArray());

            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateUser(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $updatedUser = $this->userRepository->update($id, $data);
            DB::commit();
            return $updatedUser;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteUser(int $id): bool
    {
        DB::beginTransaction();
        try {
            // Can add ABAC checks internally inside service alongside HTTP middleware if needed.
            $deleted = $this->userRepository->delete($id);

            $this->webhookService->dispatch('user.deleted', ['id' => $id]);

            DB::commit();
            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
