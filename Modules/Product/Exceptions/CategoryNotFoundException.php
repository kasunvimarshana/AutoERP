<?php

declare(strict_types=1);

namespace Modules\Product\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * Category Not Found Exception
 *
 * Thrown when a requested product category cannot be found.
 */
class CategoryNotFoundException extends NotFoundException
{
    protected string $errorCode = 'CATEGORY_NOT_FOUND';

    /**
     * Create a new category not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested category was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
