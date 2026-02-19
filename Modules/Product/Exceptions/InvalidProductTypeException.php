<?php

declare(strict_types=1);

namespace Modules\Product\Exceptions;

use Modules\Core\Exceptions\ValidationException;

/**
 * Invalid Product Type Exception
 *
 * Thrown when a product type is invalid or not supported.
 */
class InvalidProductTypeException extends ValidationException
{
    protected string $errorCode = 'INVALID_PRODUCT_TYPE';

    /**
     * The invalid product type
     */
    protected ?string $productType = null;

    /**
     * Create a new invalid product type exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $productType  The invalid product type
     * @param  array  $errors  Validation errors
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $message = 'The product type is invalid or not supported.',
        ?string $productType = null,
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errors, $code, $previous);
        $this->productType = $productType;
    }

    /**
     * Get the invalid product type
     */
    public function getProductType(): ?string
    {
        return $this->productType;
    }

    /**
     * Set the invalid product type
     */
    public function setProductType(string $productType): self
    {
        $this->productType = $productType;

        return $this;
    }
}
