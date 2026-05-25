<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Application\Contracts\UseCases\ActivateTenantServiceInterface;

final class TenantActivateCommand extends Command
{
    protected $signature = 'tenant:activate {tenant : Tenant identifier}';

    protected $description = 'Activate a tenant';

    public function __construct(private readonly ActivateTenantServiceInterface $service)
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

        $this->info('Tenant activated.');

        return self::SUCCESS;
    }
}
