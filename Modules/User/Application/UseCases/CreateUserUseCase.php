<?php
namespace Modules\User\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Events\UserCreated;
class CreateUserUseCase
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
                'password' => $data['password'],
                'status' => 'active',
            ]);
            UserCreated::dispatch($user->id);
            return $user;
        });
    }
}
