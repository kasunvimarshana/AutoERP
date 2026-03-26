<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Controllers;

use Modules\Core\Application\Contracts\ServiceInterface;

abstract class BaseController extends AuthorizedController
{
    protected ServiceInterface $service;

    protected string $resourceClass;

    protected string $dtoClass;

    public function __construct(ServiceInterface $service, string $resourceClass, string $dtoClass)
    {
        $this->service = $service;
        $this->resourceClass = $resourceClass;
        $this->dtoClass = $dtoClass;
    }

    abstract protected function getModelClass(): string;
}
