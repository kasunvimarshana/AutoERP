<?php
namespace Modules\User\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
class UpdateUserProfileUseCase
{
    public function __construct(private UserRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->repo->update($data['id'], array_filter([
                'name' => $data['name'] ?? null,
                'avatar_path' => $data['avatar_path'] ?? null,
            ], fn ($v) => $v !== null));
        });
    }
}
