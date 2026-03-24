<?php

namespace Modules\Core\Domain\Exceptions;

class NotFoundException extends DomainException
{
    public function __construct(string $entity, mixed $id = null)
    {
        $message = $id !== null
            ? "{$entity} with id '{$id}' not found"
            : "{$entity} not found";

        parent::__construct($message, 404);
    }
}
