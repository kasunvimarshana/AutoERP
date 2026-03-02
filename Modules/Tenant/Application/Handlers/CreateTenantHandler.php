<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Tenant\Application\Commands\CreateTenantCommand;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Enums\TenantStatus;

class CreateTenantHandler extends BaseHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateTenantCommand $command): Tenant
    {
        return $this->transaction(function () use ($command): Tenant {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateTenantCommand $cmd): Tenant {
                    $currency = $cmd->currency ?? config('currency.default', 'LKR');

                    $tenant = new Tenant(
                        id: null,
                        name: $cmd->name,
                        slug: $cmd->slug,
                        status: TenantStatus::Trial->value,
                        domain: $cmd->domain,
                        planCode: $cmd->planCode,
                        currency: strtoupper($currency),
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->tenantRepository->save($tenant);
                });
        });
    }
}
