<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\NumberingSequence;

interface NumberingSequenceRepositoryInterface extends RepositoryInterface
{
    public function save(NumberingSequence $sequence): NumberingSequence;

    public function findByTenantModuleAndDocumentType(int $tenantId, string $module, string $documentType): ?NumberingSequence;

    public function generateNextNumber(int $tenantId, string $module, string $documentType): string;
}
