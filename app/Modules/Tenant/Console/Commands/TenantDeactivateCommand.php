<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Services\DeactivateTenantService;

final class TenantDeactivateCommand extends Command
{
    protected $signature = 'tenant:deactivate
        {tenant : Tenant identifier}
        {expected-version : Current row version}
        {--reason= : Required lifecycle reason}';

    protected $description = 'Deactivate a tenant through the validated lifecycle.';

    public function __construct(
        private readonly DeactivateTenantService $service,
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

        $this->info('Tenant deactivated.');

        return self::SUCCESS;
    }
}
