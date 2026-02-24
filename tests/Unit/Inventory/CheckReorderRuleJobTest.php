<?php

namespace Tests\Unit\Inventory;

use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Inventory\Domain\Events\LowStockAlert;
use Modules\Inventory\Infrastructure\Jobs\CheckReorderRuleJob;
use Modules\Inventory\Infrastructure\Models\ReorderRuleModel;
use Modules\Inventory\Infrastructure\Models\StockLevelModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CheckReorderRuleJob.
 *
 * These tests mock Eloquent models so that no real DB connection is required.
 */
class CheckReorderRuleJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_has_correct_fault_tolerance_properties(): void
    {
        $job = new CheckReorderRuleJob('rule-uuid-1');

        $this->assertSame(3, $job->tries);
        $this->assertSame(30, $job->backoff);
        $this->assertSame(60, $job->timeout);
    }

    public function test_job_stores_reorder_rule_id(): void
    {
        $job = new CheckReorderRuleJob('rule-uuid-abc');

        $this->assertSame('rule-uuid-abc', $job->reorderRuleId);
    }

    public function test_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new CheckReorderRuleJob('rule-uuid-1'),
        );
    }
}
