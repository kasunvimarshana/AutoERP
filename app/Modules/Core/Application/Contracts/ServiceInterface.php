<?php

namespace Modules\Core\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServiceInterface extends ReadServiceInterface, WriteServiceInterface
{
}
