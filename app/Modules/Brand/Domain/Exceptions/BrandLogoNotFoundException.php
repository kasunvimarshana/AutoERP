<?php

declare(strict_types=1);

namespace Modules\Brand\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class BrandLogoNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('BrandLogo', $id);
    }
}
