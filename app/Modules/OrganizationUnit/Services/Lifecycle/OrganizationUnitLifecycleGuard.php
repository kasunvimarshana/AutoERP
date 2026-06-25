<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Lifecycle;

use Modules\OrganizationUnit\Exceptions\OrganizationUnitException;
use Modules\OrganizationUnit\Contracts\OrganizationUnitLifecycleBlockerInterface;

final class OrganizationUnitLifecycleGuard
{
    /** @param iterable<OrganizationUnitLifecycleBlockerInterface> $contributors */
    public function __construct(private readonly iterable $contributors) {}

    /** @return list<array{code:string,message:string,count:int}> */
    public function blockers(int $tenantId, int $organizationUnitId): array
    {
        $blockers = [];
        foreach ($this->contributors as $contributor) {
            foreach ($contributor->blockers($tenantId, $organizationUnitId) as $blocker) {
                if (($blocker['count'] ?? 0) > 0) {
                    $blockers[] = $blocker;
                }
            }
        }

        return $blockers;
    }

    public function assertClear(int $tenantId, int $organizationUnitId): void
    {
        $blockers = $this->blockers($tenantId, $organizationUnitId);
        if ($blockers === []) {
            return;
        }

        throw OrganizationUnitException::lifecycleBlocked(
            implode(' ', array_map(
                static fn (array $blocker): string => sprintf(
                    '%s (%d)',
                    $blocker['message'],
                    $blocker['count'],
                ),
                $blockers,
            )),
            ['blockers' => $blockers],
        );
    }
}
