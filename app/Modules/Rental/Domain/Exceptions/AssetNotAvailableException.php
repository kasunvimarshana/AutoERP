<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

class AssetNotAvailableException extends \RuntimeException
{
    public function __construct(int $assetId, string $reason = '')
    {
        $message = "Asset {$assetId} is not available for the requested period.";
        if ($reason !== '') {
            $message .= " {$reason}";
        }
        parent::__construct($message);
    }
}
