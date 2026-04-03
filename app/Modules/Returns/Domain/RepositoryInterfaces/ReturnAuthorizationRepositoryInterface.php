<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Returns\Domain\Entities\ReturnAuthorization;

interface ReturnAuthorizationRepositoryInterface extends RepositoryInterface
{
    public function save(ReturnAuthorization $auth): ReturnAuthorization;
    public function findByRmaNumber(int $tenantId, string $rmaNumber): ?ReturnAuthorization;
    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
