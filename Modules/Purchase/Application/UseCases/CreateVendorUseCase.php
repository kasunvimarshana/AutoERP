<?php
namespace Modules\Purchase\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Domain\Contracts\VendorRepositoryInterface;
class CreateVendorUseCase
{
    public function __construct(private VendorRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(fn() => $this->repo->create($data));
    }
}
