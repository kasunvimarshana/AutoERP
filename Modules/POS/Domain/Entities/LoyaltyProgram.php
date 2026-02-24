<?php

namespace Modules\POS\Domain\Entities;

/**
 * Immutable domain entity representing a POS loyalty program.
 *
 * Customers earn points at a configurable rate (points per currency unit spent)
 * and can redeem them at a configurable redemption rate (points per currency unit discount).
 */
readonly class LoyaltyProgram
{
    public function __construct(
        public string  $id,
        public string  $tenant_id,
        public string  $name,
        public string  $points_per_currency_unit,
        public string  $redemption_rate,
        public bool    $is_active,
        public ?string $description,
    ) {}
}
