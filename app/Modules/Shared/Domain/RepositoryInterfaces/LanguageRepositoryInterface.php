<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Shared\Domain\Entities\Language;

interface LanguageRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Language;

    public function save(Language $language): Language;
}
