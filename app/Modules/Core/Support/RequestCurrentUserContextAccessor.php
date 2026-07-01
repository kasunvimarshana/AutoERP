<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\CurrentUserContext;

final class RequestCurrentUserContextAccessor implements CurrentUserContextAccessorInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly string $requestAttribute,
    ) {}

    public function current(): ?CurrentUserContext
    {
        $context = $this->request()->attributes->get($this->requestAttribute);

        return $context instanceof CurrentUserContext ? $context : null;
    }

    public function requireCurrent(): CurrentUserContext
    {
        return $this->current()
            ?? throw new LogicException('Current user context is not available on the active request.');
    }

    public function currentUserId(): ?int
    {
        return $this->current()?->userId();
    }

    public function currentApplicationId(): ?string
    {
        return $this->current()?->applicationId();
    }

    private function request(): Request
    {
        return $this->container->make(Request::class);
    }
}
