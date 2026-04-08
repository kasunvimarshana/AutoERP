<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entities;

use App\Shared\Domain\Contracts\EntityContract;
use App\Shared\Domain\ValueObjects\Uuid;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Catalog\ValueObjects\Money;
use App\Domain\Catalog\Events\ProductWasCreated;
use App\Domain\Catalog\Events\ProductPriceWasChanged;
use App\Domain\Catalog\Exceptions\InvalidProductException;

/**
 * Product — Domain Entity (Catalog bounded context)
 *
 * Represents a product in the catalog with its business rules.
 * All state changes go through explicit public methods that enforce
 * invariants and raise domain events.
 */
final class Product implements EntityContract
{
    private array $domainEvents = [];

    private function __construct(
        private readonly Uuid        $id,
        private ProductName          $name,
        private Money                $price,
        private bool                 $active,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    // -------------------------------------------------------------------------
    // Named Constructors
    // -------------------------------------------------------------------------

    public static function create(
        ProductName $name,
        Money       $price,
    ): self {
        $product = new self(
            id:        Uuid::generate(),
            name:      $name,
            price:     $price,
            active:    true,
            createdAt: new \DateTimeImmutable(),
        );

        $product->recordEvent(new ProductWasCreated(
            productId: $product->id,
            name:      $name,
            price:     $price,
        ));

        return $product;
    }

    public static function reconstitute(
        Uuid                $id,
        ProductName         $name,
        Money               $price,
        bool                $active,
        \DateTimeImmutable  $createdAt,
    ): self {
        return new self($id, $name, $price, $active, $createdAt);
    }

    // -------------------------------------------------------------------------
    // EntityContract
    // -------------------------------------------------------------------------

    public function id(): Uuid { return $this->id; }

    public function equals(EntityContract $other): bool
    {
        return $other instanceof self && $this->id->equals($other->id);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function name(): ProductName          { return $this->name; }
    public function price(): Money               { return $this->price; }
    public function isActive(): bool             { return $this->active; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }

    // -------------------------------------------------------------------------
    // Business Behaviour
    // -------------------------------------------------------------------------

    public function rename(ProductName $newName): void
    {
        $this->name = $newName;
    }

    public function changePrice(Money $newPrice): void
    {
        if ($newPrice->isZero()) {
            throw InvalidProductException::priceCannotBeZero($this->id);
        }

        $old         = $this->price;
        $this->price = $newPrice;

        $this->recordEvent(new ProductPriceWasChanged(
            productId: $this->id,
            oldPrice:  $old,
            newPrice:  $newPrice,
        ));
    }

    public function activate(): void   { $this->active = true; }
    public function deactivate(): void { $this->active = false; }

    // -------------------------------------------------------------------------
    // Domain Events
    // -------------------------------------------------------------------------

    /** @return array<object> */
    public function releaseEvents(): array
    {
        $events            = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
