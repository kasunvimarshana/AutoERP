<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;

abstract class BaseService
{
    public function __construct(protected BaseRepositoryInterface $repository) {}

    /**
     * Delegate unknown method calls to the underlying repository.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (method_exists($this->repository, $method)) {
            return $this->repository->{$method}(...$arguments);
        }

        throw new \BadMethodCallException(
            sprintf('Method %s::%s does not exist.', static::class, $method)
        );
    }
}
