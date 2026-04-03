<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\GS1\Domain\Entities\Gs1Barcode;

interface Gs1BarcodeRepositoryInterface extends RepositoryInterface
{
    public function save(Gs1Barcode $barcode): Gs1Barcode;

    public function findByIdentifier(int $tenantId, int $identifierId): Collection;

    public function findPrimary(int $tenantId, int $identifierId): ?Gs1Barcode;
}
