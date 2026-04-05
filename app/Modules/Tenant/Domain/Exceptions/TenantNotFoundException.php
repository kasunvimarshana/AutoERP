<?php
declare(strict_types=1);
namespace Modules\Tenant\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class TenantNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct('Tenant', $id);
    }
}
