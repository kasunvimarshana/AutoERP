<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Unit\Domain;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Events\ProductPriceWasChanged;
use App\Domain\Catalog\Events\ProductWasCreated;
use App\Domain\Catalog\Exceptions\InvalidProductException;
use App\Domain\Catalog\ValueObjects\Money;
use App\Domain\Catalog\ValueObjects\ProductName;
use Archify\DddArchitect\Tests\TestCase;

/**
 * Pure unit tests for the Product domain entity.
 * No infrastructure, no DB — only domain logic.
 */
final class ProductTest extends TestCase
{
    private function makeProduct(string $name = 'Widget', int $price = 999): Product
    {
        return Product::create(
            name:  ProductName::fromString($name),
            price: Money::of($price, 'USD'),
        );
    }

    /** @test */
    public function it_creates_a_product_with_generated_id(): void
    {
        $product = $this->makeProduct();

        $this->assertNotNull($product->id());
        $this->assertNotEmpty($product->id()->value());
    }

    /** @test */
    public function a_new_product_is_active_by_default(): void
    {
        $product = $this->makeProduct();

        $this->assertTrue($product->isActive());
    }

    /** @test */
    public function it_raises_a_created_event_on_creation(): void
    {
        $product = $this->makeProduct('Gadget', 1999);
        $events  = $product->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductWasCreated::class, $events[0]);
        $this->assertSame('Gadget', $events[0]->name->value());
    }

    /** @test */
    public function releasing_events_clears_the_queue(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        $this->assertEmpty($product->releaseEvents());
    }

    /** @test */
    public function it_changes_price_and_raises_event(): void
    {
        $product  = $this->makeProduct('Widget', 999);
        $product->releaseEvents(); // clear creation event

        $newPrice = Money::of(1299, 'USD');
        $product->changePrice($newPrice);

        $this->assertTrue($product->price()->equals($newPrice));

        $events = $product->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductPriceWasChanged::class, $events[0]);
        $this->assertSame(999,  $events[0]->oldPrice->amount());
        $this->assertSame(1299, $events[0]->newPrice->amount());
    }

    /** @test */
    public function it_throws_when_changing_price_to_zero(): void
    {
        $this->expectException(InvalidProductException::class);
        $this->expectExceptionMessageMatches('/price cannot be zero/');

        $product = $this->makeProduct();
        $product->changePrice(Money::of(0, 'USD'));
    }

    /** @test */
    public function it_can_be_deactivated_and_reactivated(): void
    {
        $product = $this->makeProduct();
        $product->deactivate();

        $this->assertFalse($product->isActive());

        $product->activate();
        $this->assertTrue($product->isActive());
    }

    /** @test */
    public function two_products_with_same_id_are_equal(): void
    {
        $product = $this->makeProduct();

        $clone = Product::reconstitute(
            id:        $product->id(),
            name:      $product->name(),
            price:     $product->price(),
            active:    $product->isActive(),
            createdAt: $product->createdAt(),
        );

        $this->assertTrue($product->equals($clone));
    }

    /** @test */
    public function two_products_with_different_ids_are_not_equal(): void
    {
        $a = $this->makeProduct('Widget');
        $b = $this->makeProduct('Widget');

        $this->assertFalse($a->equals($b));
    }

    /** @test */
    public function it_renames_the_product(): void
    {
        $product = $this->makeProduct('OldName');
        $product->rename(ProductName::fromString('NewName'));

        $this->assertSame('NewName', $product->name()->value());
    }
}
