<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Application\DTOs\TenantValueData;
use Modules\Tenant\Application\UseCases\CreateTenantService;

final class TenantCreateCommand extends Command
{
    protected $signature = 'tenant:create
        {code : Tenant code}
        {name : Tenant display name}
        {--slug= : Optional slug}
        {--status=active : Tenant status (active|inactive|suspended)}
        {--is-isolated=1 : Whether tenant is isolated (1/0)}
        {--isolation-key= : Optional explicit isolation key}
        {--configuration-scope= : Optional configuration scope}';

    protected $description = 'Create a tenant';

    public function __construct(private readonly CreateTenantService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->execute([
            'code' => (string) $this->argument('code'),
            'name' => (string) $this->argument('name'),
            'slug' => $this->option('slug') !== null ? (string) $this->option('slug') : null,
            'status' => (string) $this->option('status'),
            'is_isolated' => ((string) $this->option('is-isolated')) !== '0',
            'isolation_key' => $this->option('isolation-key') !== null ? (string) $this->option('isolation-key') : null,
            'configuration_scope' => $this->option('configuration-scope') !== null
                ? (string) $this->option('configuration-scope')
                : null,
        ]);

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $tenant = $result->valueOrFail();
        if (! $tenant instanceof TenantValueData) {
            $this->error('Invalid tenant response.');

            return self::FAILURE;
        }

        $this->info(sprintf('Tenant "%s" (%s) created.', $tenant->name, $tenant->code));

        return self::SUCCESS;
    }
}
