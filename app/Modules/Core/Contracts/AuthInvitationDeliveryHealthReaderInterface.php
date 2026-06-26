<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface AuthInvitationDeliveryHealthReaderInterface
{
    /** @return array{counts:array<string,int>,failed:int,stale:int} */
    public function health(?int $tenantId = null): array;

    /** @return list<array<string,mixed>> */
    public function failed(int $limit = 20): array;
}
