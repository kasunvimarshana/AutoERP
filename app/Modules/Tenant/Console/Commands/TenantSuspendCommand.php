<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\SuspendTenantService;

final class TenantSuspendCommand extends Command
{
    protected $signature = 'tenant:suspend
        {tenant : Tenant identifier}
        {expected-version : Current row version}
        {--reason= : Required lifecycle reason}';

    protected $description = 'Suspended a tenant through the validated lifecycle.';

    public function __construct(private readonly SuspendTenantService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $reason = trim((string) $this->option('reason'));
        if ($reason === '') {
            $this->error('A lifecycle reason is required.');

            return self::FAILURE;
        }

        $result = $this->service->execute(
            (string) $this->argument('tenant'),
            (int) $this->argument('expected-version'),
            $reason,
        );
        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $this->info('Tenant suspended.');

        return self::SUCCESS;
    }
}
