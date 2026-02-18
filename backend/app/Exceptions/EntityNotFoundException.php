<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Entity Not Found Exception
 * 
 * Thrown when a requested entity does not exist
 */
class EntityNotFoundException extends BusinessException
{
    protected int $statusCode = 404;
    protected string $errorCode = 'ENTITY_NOT_FOUND';

    public function __construct(string $entityType, string|int $identifier)
    {
        parent::__construct(
            "Entity not found: {$entityType}",
            [
                'entity_type' => $entityType,
                'identifier' => $identifier,
            ]
        );
    }
}
