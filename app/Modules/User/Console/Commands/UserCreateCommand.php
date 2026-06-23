<?php

declare(strict_types=1);

namespace Modules\User\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Services\UserService;

final class UserCreateCommand extends Command
{
    protected $signature = 'user:create
        {tenant_id : Tenant identifier}
        {first_name : First name}
        {email : Email address}
        {password : Plain-text password}
        {--status=active : active|inactive|suspended}';

    protected $description = 'Create a tenant-owned user through the User module service.';

    public function __construct(
        private readonly UserService $service,
        private readonly TenantExecutionContextInterface $tenantExecution,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        if ($tenantId < 1) {
            $this->error('Tenant identifier must be a positive integer.');

            return self::FAILURE;
        }

        $result = $this->tenantExecution->runForTenant(
            $tenantId,
            fn () => $this->service->create([
                'first_name' => (string) $this->argument('first_name'),
                'email' => (string) $this->argument('email'),
                'password' => (string) $this->argument('password'),
                'tenant_id' => $tenantId,
                'status' => (string) $this->option('status'),
            ]),
        );

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $record = $result->valueOrFail();
        $id = method_exists($record, 'id') ? $record->id() : null;

        $this->info(sprintf('User created successfully%s.', $id !== null ? ' (ID: '.$id.')' : ''));

        return self::SUCCESS;
    }
}
