<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Factories;

use Illuminate\Contracts\Container\Container;
use Modules\Inventory\Domain\Contracts\AllocationRuleInterface;

final class AllocationRuleFactory
{
    /**
     * @param array<string, class-string<AllocationRuleInterface>> $rules
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $rules,
    ) {
    }

    /**
     * @param string[] $ruleKeys
     * @return AllocationRuleInterface[]
     */
    public function makeMany(array $ruleKeys): array
    {
        $instances = [];

        foreach ($ruleKeys as $ruleKey) {
            if (!isset($this->rules[$ruleKey])) {
                continue;
            }

            $instances[] = $this->container->make($this->rules[$ruleKey]);
        }

        return $instances;
    }
}
