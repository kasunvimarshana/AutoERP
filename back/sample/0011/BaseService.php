<?php

declare(strict_types=1);

namespace Modules\Core\Application\Services;

use Throwable;
use Illuminate\Support\Facades\DB;

/**
 * BaseService
 *
 * All application services extend this. Provides:
 *  - execute() public entry point (wraps in DB transaction)
 *  - handle() abstract — subclass implements the use case
 *  - Consistent exception propagation
 */
abstract class BaseService
{
    public function execute(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            return $this->handle($data);
        });
    }

    abstract protected function handle(array $data): mixed;
}
