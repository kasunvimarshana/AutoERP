<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Application\UseCases\SuspendTenantService;

final class TenantSuspendCommand extends Command
{
    protected $signature = 'tenant:suspend {tenant : Tenant identifier}';

    protected $description = 'Suspend a tenant';

    public function __construct(private readonly SuspendTenantService $service)
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

        $this->info('Tenant suspended.');

        return self::SUCCESS;
    }
}
