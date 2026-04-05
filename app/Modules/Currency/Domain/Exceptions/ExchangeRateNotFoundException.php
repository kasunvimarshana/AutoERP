<?php
declare(strict_types=1);
namespace Modules\Currency\Domain\Exceptions;

class ExchangeRateNotFoundException extends \RuntimeException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("No active exchange rate found from '{$from}' to '{$to}'.");
    }
}
