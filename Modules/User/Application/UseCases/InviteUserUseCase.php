<?php
namespace Modules\User\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Events\UserInvited;
class InviteUserUseCase
{
    public function __construct(private UserRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $user = $this->repo->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(\Illuminate\Support\Str::random(16)),
                'status' => 'pending_verification',
                'invited_by' => $data['invited_by'] ?? null,
            ]);
            UserInvited::dispatch($user->id);
            return $user;
        });
    }
}
