<?php

declare(strict_types=1);

namespace Modules\Tax\Services\Party;

use InvalidArgumentException;
use LogicException;
use Modules\Tax\Contracts\TaxPartyResolverInterface;
use Modules\Tax\Data\TaxPartySnapshot;

final class TaxPartyResolverRegistry
{
    /**
     * @var array<string, TaxPartyResolverInterface>
     */
    private array $resolvers = [];

    /**
     * @param iterable<TaxPartyResolverInterface> $resolvers
     */
    public function __construct(iterable $resolvers)
    {
        foreach ($resolvers as $resolver) {
            if (! $resolver instanceof TaxPartyResolverInterface) {
                throw new LogicException('Tax party resolver registry contains an invalid service.');
            }
            $type = trim($resolver->partyType());
            if ($type === '' || isset($this->resolvers[$type])) {
                throw new LogicException('Tax party resolver types must be non-empty and unique.');
            }
            $this->resolvers[$type] = $resolver;
        }
    }

    public function resolve(
        string $partyType,
        int $tenantId,
        ?int $organizationUnitId,
        int $partyId,
    ): TaxPartySnapshot {
        $resolver = $this->resolvers[$partyType] ?? null;
        if (! $resolver instanceof TaxPartyResolverInterface) {
            throw new InvalidArgumentException("Unsupported tax party type [{$partyType}].");
        }

        return $resolver->resolve($tenantId, $organizationUnitId, $partyId);
    }
}
