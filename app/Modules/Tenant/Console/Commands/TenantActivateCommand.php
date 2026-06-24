<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Services\ActivateTenantService;

final class TenantActivateCommand extends Command
{
    protected $signature = 'tenant:activate
        {tenant : Tenant identifier}
        {expected-version : Current row version}
        {--reason= : Required lifecycle reason}';

    protected $description = 'Activate a tenant through the validated lifecycle.';

    public function __construct(
        private readonly ActivateTenantService $service,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $reason = trim((string) $this->option('reason'));
        if ($reason === '') {
            $this->error('A lifecycle reason is required.');

            return self::FAILURE;
        }

        $result = $this->executionContext->runAsControlPlane(
            fn () => $this->service->execute(
                (string) $this->argument('tenant'),
                (int) $this->argument('expected-version'),
                $reason,
            ),
        );
        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $this->info('Tenant activated.');

        return self::SUCCESS;
    }
}
