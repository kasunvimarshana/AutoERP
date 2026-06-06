<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\DeactivateTenantService;

final class TenantDeactivateCommand extends Command
{
    protected $signature = 'tenant:deactivate {tenant : Tenant identifier}';

    protected $description = 'Deactivate a tenant';

    public function __construct(private readonly DeactivateTenantService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->execute((string) $this->argument('tenant'));

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $this->info('Tenant deactivated.');

        return self::SUCCESS;
    }
}
