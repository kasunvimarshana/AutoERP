<?php

declare(strict_types=1);

namespace Modules\Tenant\Data;

final readonly class TenantReadiness
{
    /**
     * @param list<array{key:string,label:string,ready:bool,guidance:string}> $checks
     */
    public function __construct(public array $checks) {}

    public function readyForActivation(): bool
    {
        foreach ($this->checks as $check) {
            if (! $check['ready']) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function blockingMessages(): array
    {
        return array_values(array_map(
            static fn (array $check): string => $check['guidance'],
            array_filter(
                $this->checks,
                static fn (array $check): bool => ! $check['ready'],
            ),
        ));
    }

    /** @return array{ready_for_activation:bool,checks:list<array{key:string,label:string,ready:bool,guidance:string}>} */
    public function toArray(): array
    {
        return [
            'ready_for_activation' => $this->readyForActivation(),
            'checks' => $this->checks,
        ];
    }
}
