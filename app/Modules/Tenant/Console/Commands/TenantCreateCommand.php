<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Services\CreateTenantService;

final class TenantCreateCommand extends Command
{
    protected $signature = 'tenant:create
        {code : Stable tenant code}
        {name : Tenant display name}
        {--slug=}
        {--base-currency-id=}';

    protected $description = 'Create a draft tenant';

    public function __construct(
        private readonly CreateTenantService $service,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->executionContext->runAsControlPlane(fn () => $this->service->execute([
            'code' => (string) $this->argument('code'),
            'name' => (string) $this->argument('name'),
            'slug' => $this->option('slug'),
            'base_currency_id' => $this->option('base-currency-id'),
        ]));

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $tenant = $result->valueOrFail();
        if (! $tenant instanceof DataRecord) {
            $this->error('Invalid tenant response.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Draft tenant "%s" (%s) created. Add and verify a primary domain before activation.',
            $tenant->require('name'),
            $tenant->require('code'),
        ));

        return self::SUCCESS;
    }
}
