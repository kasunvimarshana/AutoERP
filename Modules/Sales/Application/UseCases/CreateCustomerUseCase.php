<?php
namespace Modules\Sales\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Domain\Contracts\CustomerRepositoryInterface;
class CreateCustomerUseCase
{
    public function __construct(private CustomerRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(fn() => $this->repo->create($data));
    }
}
