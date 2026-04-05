<?php
declare(strict_types=1);
namespace Modules\Currency\Domain\Exceptions;

class CurrencyNotFoundException extends \RuntimeException
{
    public function __construct(string $code)
    {
        parent::__construct("Currency '{$code}' not found.");
    }
}
