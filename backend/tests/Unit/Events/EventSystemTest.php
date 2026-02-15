<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\Product\ProductCreated;
use App\Events\Inventory\LowStockDetected;
use App\Notifications\LowStockNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class EventSystemTest extends TestCase
{
    public function test_product_created_event_structure(): void
    {
        $product = new \App\Modules\Product\Models\Product([
            'id' => 1,
            'tenant_id' => 1,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
        ]);

        $event = new ProductCreated($product, 1, 1);

        $this->assertEquals(1, $event->tenantId);
        $this->assertEquals(1, $event->userId);
        $this->assertEquals('TEST-001', $event->product->sku);
    }

    public function test_low_stock_detected_event_structure(): void
    {
        $event = new LowStockDetected(
            productId: 1,
            productName: 'Test Product',
            productSku: 'TEST-001',
            currentQuantity: 5.0,
            minimumQuantity: 10.0,
            locationId: 1,
            tenantId: 1
        );

        $this->assertEquals(1, $event->tenantId);
        $this->assertEquals('TEST-001', $event->productSku);
        $this->assertEquals(5.0, $event->currentQuantity);
        $this->assertEquals(10.0, $event->minimumQuantity);
    }

    public function test_notification_structure(): void
    {
        $notification = new LowStockNotification(
            productId: 1,
            productName: 'Test Product',
            productSku: 'TEST-001',
            currentQuantity: 5.0,
            minimumQuantity: 10.0,
            locationId: 1
        );

        $this->assertContains('database', $notification->via(new User()));
    }
}
