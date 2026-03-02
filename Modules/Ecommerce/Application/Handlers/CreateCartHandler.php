<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Ecommerce\Application\Commands\CreateCartCommand;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontCart;
use Modules\Ecommerce\Domain\Enums\StorefrontCartStatus;

class CreateCartHandler extends BaseHandler
{
    public function __construct(
        private readonly StorefrontCartRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateCartCommand $command): StorefrontCart
    {
        return $this->transaction(function () use ($command): StorefrontCart {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateCartCommand $cmd): StorefrontCart {
                    $token = (string) Str::uuid();

                    return $this->repository->save(new StorefrontCart(
                        id: null,
                        tenantId: $cmd->tenantId,
                        userId: $cmd->userId,
                        token: $token,
                        status: StorefrontCartStatus::Active->value,
                        currency: $cmd->currency,
                        subtotal: '0.0000',
                        taxAmount: '0.0000',
                        totalAmount: '0.0000',
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
