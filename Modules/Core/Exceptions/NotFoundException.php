<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Exception thrown when a requested resource is not found
 */
class NotFoundException extends Exception
{
    /**
     * Create a new not found exception
     *
     * @param  string  $resource  The resource type
     * @param  string|int  $identifier  The identifier used
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $resource,
        string|int $identifier,
        ?\Throwable $previous = null
    ) {
        $message = "{$resource} not found with identifier: {$identifier}";
        parent::__construct($message, 404, $previous);
    }

    /**
     * Create exception for entity not found by ID
     */
    public static function entity(string $entityClass, string|int $id): static
    {
        $entityName = class_basename($entityClass);

        return new static($entityName, $id);
    }

    /**
     * Create exception for resource not found
     */
    public static function resource(string $resourceType, string|int $identifier): static
    {
        return new static($resourceType, $identifier);
    }
}
