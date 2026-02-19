<?php

declare(strict_types=1);

namespace Modules\Pricing\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * Price Not Found Exception
 *
 * Thrown when a requested price cannot be found.
 */
class PriceNotFoundException extends NotFoundException
{
    protected string $errorCode = 'PRICE_NOT_FOUND';

    /**
     * Create a new price not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested price was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
