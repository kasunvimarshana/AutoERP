<?php
namespace Modules\Tenant\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Events\TenantSuspended;
class SuspendTenantUseCase
{
    public function __construct(private TenantRepositoryInterface $repo) {}
    public function execute(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            $result = $this->repo->suspend($data['tenant_id']);
            TenantSuspended::dispatch($data['tenant_id']);
            return $result;
        });
    }
}
