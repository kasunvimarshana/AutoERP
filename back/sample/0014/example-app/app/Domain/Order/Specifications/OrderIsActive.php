<?php

namespace App\Domain\Order\Specifications;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;

/**
 * Specification: an Order is considered "active" when it has not been
 * cancelled and has not been fully shipped.
 */
final class OrderIsActive
{
    public function isSatisfiedBy(Order $order): bool
    {
        return ! in_array($order->status(), [
            OrderStatus::Cancelled,
            OrderStatus::Shipped,
        ]);
    }

    public function and(self $other): AndSpecification
    {
        return new AndSpecification($this, $other);
    }

    public function not(): NotSpecification
    {
        return new NotSpecification($this);
    }
}

final class AndSpecification
{
    public function __construct(
        private readonly mixed $left,
        private readonly mixed $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            && $this->right->isSatisfiedBy($candidate);
    }
}

final class NotSpecification
{
    public function __construct(private readonly mixed $spec) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return ! $this->spec->isSatisfiedBy($candidate);
    }
}
