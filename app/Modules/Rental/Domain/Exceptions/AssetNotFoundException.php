<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

class AssetNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Asset with ID {$id} not found.");
    }
}
