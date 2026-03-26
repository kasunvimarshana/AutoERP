<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\ServiceInterface;

abstract class BaseController extends Controller
{
    use AuthorizesRequests {
        authorize as protected laravelAuthorize;
    }

    protected ServiceInterface $service;

    protected string $resourceClass;

    protected string $dtoClass;

    public function __construct(ServiceInterface $service, string $resourceClass, string $dtoClass)
    {
        $this->service = $service;
        $this->resourceClass = $resourceClass;
        $this->dtoClass = $dtoClass;
    }

    public function authorize($ability, $arguments = [])
    {
        // return $this->laravelAuthorize($ability, $arguments);
        return true;
    }

    abstract protected function getModelClass(): string;
}
