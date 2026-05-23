<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Repositories;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface LanguageRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code, array $with = []): ?Model;

    public function findByName(string $name, array $with = []): ?Model;
}
