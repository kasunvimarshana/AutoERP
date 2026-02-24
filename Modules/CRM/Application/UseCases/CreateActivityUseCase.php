<?php
namespace Modules\CRM\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Domain\Contracts\ActivityRepositoryInterface;
class CreateActivityUseCase
{
    public function __construct(private ActivityRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(fn() => $this->repo->create($data));
    }
}
