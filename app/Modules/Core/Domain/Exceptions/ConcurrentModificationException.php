<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Exceptions;

class ConcurrentModificationException extends DomainException
{
    public function __construct(string $entity, mixed $id = null)
    {
        $message = $id !== null
            ? "{$entity} with id '{$id}' was modified by another request. Please reload and retry."
            : "{$entity} was modified by another request. Please reload and retry.";

        parent::__construct($message, 409);
    }
}
