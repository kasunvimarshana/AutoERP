<?php
namespace Modules\Tenant\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Events\TenantCreated;
class CreateTenantUseCase
{
    public function __construct(private TenantRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        if (empty(trim($data['name'] ?? ''))) {
            throw new \DomainException('Tenant name is required.');
        }
        return DB::transaction(function () use ($data) {
            $tenant = $this->repo->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $data['name'],
                'slug' => \Illuminate\Support\Str::slug($data['name']),
                'domain' => $data['domain'] ?? null,
                'status' => 'active',
                'timezone' => $data['timezone'] ?? 'UTC',
                'default_currency' => $data['default_currency'] ?? 'USD',
                'locale' => $data['locale'] ?? 'en',
            ]);
            TenantCreated::dispatch($tenant->id, $tenant->name);
            return $tenant;
        });
    }
}
