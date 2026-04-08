<?php

namespace App\Domain\Order\Entities;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Events\OrderWasPlaced;
use App\Domain\Order\Events\OrderWasCancelled;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\Contracts\AggregateRoot;
use App\Domain\Shared\Contracts\EntityContract;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Money;

/**
 * Order — the central Aggregate Root for the Order bounded context.
 *
 * All business rules around an order (placing, cancelling, etc.) live here.
 * Infrastructure (Eloquent, HTTP) is never imported into this class.
 */
final class Order implements EntityContract, AggregateRoot
{
    /** @var array<DomainEvent> */
    private array $domainEvents = [];

    /** @var array<OrderLine> */
    private array $lines = [];

    private function __construct(
        private readonly OrderId     $id,
        private readonly string      $customerId,
        private OrderStatus          $status,
        private readonly \DateTimeImmutable $placedAt,
    ) {
    }

    // -------------------------------------------------------------------------
    // Factory Methods
    // -------------------------------------------------------------------------

    public static function place(string $customerId): self
    {
        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            status: OrderStatus::Pending,
            placedAt: new \DateTimeImmutable(),
        );

        $order->recordEvent(new OrderWasPlaced($order->id, $customerId));

        return $order;
    }

    public static function reconstitute(
        string $id,
        string $customerId,
        string $status,
        string $placedAt,
    ): self {
        return new self(
            id: OrderId::from($id),
            customerId: $customerId,
            status: OrderStatus::from($status),
            placedAt: new \DateTimeImmutable($placedAt),
        );
    }

    // -------------------------------------------------------------------------
    // Business Behaviour
    // -------------------------------------------------------------------------

    public function cancel(string $reason): void
    {
        if ($this->status === OrderStatus::Cancelled) {
            throw OrderException::alreadyCancelled($this->id->value());
        }

        if ($this->status === OrderStatus::Shipped) {
            throw OrderException::cannotCancelShipped($this->id->value());
        }

        $this->status = OrderStatus::Cancelled;
        $this->recordEvent(new OrderWasCancelled($this->id, $reason));
    }

    public function ship(): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw OrderException::mustBePaidBeforeShipping($this->id->value());
        }

        $this->status = OrderStatus::Shipped;
    }

    public function markPaid(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw OrderException::cannotMarkPaid($this->id->value(), $this->status->value);
        }

        $this->status = OrderStatus::Paid;
    }

    public function total(): Money
    {
        return array_reduce(
            $this->lines,
            fn (Money $carry, OrderLine $line) => $carry->add($line->subtotal()),
            Money::zero('USD')
        );
    }

    // -------------------------------------------------------------------------
    // Identity (EntityContract)
    // -------------------------------------------------------------------------

    public function id(): OrderId
    {
        return $this->id;
    }

    public function equals(EntityContract $other): bool
    {
        return $other instanceof self && $this->id->equals($other->id);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function customerId(): string   { return $this->customerId; }
    public function status(): OrderStatus  { return $this->status; }
    public function placedAt(): \DateTimeImmutable { return $this->placedAt; }
    public function lines(): array         { return $this->lines; }

    // -------------------------------------------------------------------------
    // Domain Events (AggregateRoot)
    // -------------------------------------------------------------------------

    public function pullDomainEvents(): array
    {
        $events            = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
