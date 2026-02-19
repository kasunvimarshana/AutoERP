<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Service Exception
 *
 * Thrown when service layer operations fail
 */
class ServiceException extends BaseException
{
    /**
     * @var int HTTP status code
     */
    protected int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Create exception for business rule violation
     */
    public static function businessRuleViolation(string $rule, string $reason = ''): static
    {
        $message = "Business rule violation: $rule";
        if ($reason) {
            $message .= " - $reason";
        }
        $exception = new static($message);
        $exception->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        $exception->errorData = ['rule' => $rule, 'reason' => $reason];

        return $exception;
    }

    /**
     * Create exception for validation failure
     *
     * @param  array<string, mixed>  $errors
     */
    public static function validationFailed(array $errors): static
    {
        $exception = new static('Validation failed');
        $exception->statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        $exception->errorData = $errors;

        return $exception;
    }

    /**
     * Create exception for unauthorized access
     */
    public static function unauthorized(string $action = ''): static
    {
        $message = 'Unauthorized access';
        if ($action) {
            $message .= " to $action";
        }
        $exception = new static($message);
        $exception->statusCode = Response::HTTP_UNAUTHORIZED;

        return $exception;
    }

    /**
     * Create exception for forbidden access
     */
    public static function forbidden(string $resource = ''): static
    {
        $message = 'Access forbidden';
        if ($resource) {
            $message .= " to $resource";
        }
        $exception = new static($message);
        $exception->statusCode = Response::HTTP_FORBIDDEN;

        return $exception;
    }
}
