<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Configuration\Domain\Entities\Language;

interface LanguageRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Language;

    public function save(Language $language): Language;
}
