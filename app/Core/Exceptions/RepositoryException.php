<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Repository Exception
 *
 * Thrown when repository operations fail
 */
class RepositoryException extends BaseException
{
    /**
     * @var int HTTP status code
     */
    protected int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Create exception for entity not found
     */
    public static function notFound(string $entity, int|string $id): static
    {
        $exception = new static("$entity with ID $id not found");
        $exception->statusCode = Response::HTTP_NOT_FOUND;
        $exception->errorData = [
            'entity' => $entity,
            'id' => $id,
        ];

        return $exception;
    }

    /**
     * Create exception for create failure
     */
    public static function createFailed(string $entity, string $reason = ''): static
    {
        $message = "Failed to create $entity";
        if ($reason) {
            $message .= ": $reason";
        }
        $exception = new static($message);
        $exception->errorData = ['entity' => $entity];

        return $exception;
    }

    /**
     * Create exception for update failure
     */
    public static function updateFailed(string $entity, int|string $id, string $reason = ''): static
    {
        $message = "Failed to update $entity with ID $id";
        if ($reason) {
            $message .= ": $reason";
        }
        $exception = new static($message);
        $exception->errorData = ['entity' => $entity, 'id' => $id];

        return $exception;
    }

    /**
     * Create exception for delete failure
     */
    public static function deleteFailed(string $entity, int|string $id, string $reason = ''): static
    {
        $message = "Failed to delete $entity with ID $id";
        if ($reason) {
            $message .= ": $reason";
        }
        $exception = new static($message);
        $exception->errorData = ['entity' => $entity, 'id' => $id];

        return $exception;
    }
}
