<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantSubscriptionOperation;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionCommandInput;
use PHPUnit\Framework\TestCase;

final class TenantSubscriptionCommandInputTest extends TestCase
{
    private TenantSubscriptionCommandInput $input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->input = new TenantSubscriptionCommandInput(new class implements ClockInterface
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-06-26 10:00:00 UTC');
            }
        });
    }

    public function test_it_rejects_future_subscription_starts_until_scheduling_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Future subscription starts are not supported');

        $this->input->resolve(TenantSubscriptionOperation::ASSIGN, [
            'tenant_plan_revision_id' => 12,
            'contract_status' => 'active',
            'starts_at' => '2026-06-27 10:00:00 UTC',
        ], null);
    }

    public function test_it_builds_one_legal_trial_period_without_contract_end_date(): void
    {
        $resolved = $this->input->resolve(TenantSubscriptionOperation::ASSIGN, [
            'tenant_plan_revision_id' => 12,
            'contract_status' => 'trial',
            'starts_at' => '2026-06-26 09:00:00 UTC',
            'trial_ends_at' => '2026-07-10 09:00:00 UTC',
        ], null);

        self::assertSame('trial', $resolved['contract_status']);
        self::assertSame('2026-06-26 09:00:00', $resolved['period']['starts_at']);
        self::assertSame('2026-07-10 09:00:00', $resolved['period']['trial_ends_at']);
        self::assertNull($resolved['period']['ends_at']);
    }

    public function test_it_rejects_trial_and_contract_end_dates_in_the_same_revision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot also define a contract end date');

        $this->input->resolve(TenantSubscriptionOperation::ASSIGN, [
            'tenant_plan_revision_id' => 12,
            'contract_status' => 'trial',
            'trial_ends_at' => '2026-07-10 10:00:00 UTC',
            'ends_at' => '2026-08-10 10:00:00 UTC',
        ], null);
    }

    public function test_it_rejects_the_generic_extend_command_for_trials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only active fixed-term subscriptions can be extended');

        $this->input->resolve(TenantSubscriptionOperation::EXTEND, [
            'ends_at' => '2026-08-10 10:00:00 UTC',
        ], $this->currentSubscription('trial', null));
    }

    public function test_it_extends_only_an_active_fixed_term_subscription(): void
    {
        $resolved = $this->input->resolve(TenantSubscriptionOperation::EXTEND, [
            'ends_at' => '2026-08-10 10:00:00 UTC',
        ], $this->currentSubscription('active', '2026-07-10 10:00:00 UTC'));

        self::assertSame(17, $resolved['plan_revision_id']);
        self::assertSame('active', $resolved['contract_status']);
        self::assertSame('2026-06-01 00:00:00', $resolved['period']['starts_at']);
        self::assertNull($resolved['period']['trial_ends_at']);
        self::assertSame('2026-08-10 10:00:00', $resolved['period']['ends_at']);
    }

    private function currentSubscription(string $contractStatus, ?string $endsAt): DataRecord
    {
        return new DataRecord([
            'id' => 1,
            'tenant_plan_revision_id' => 17,
            'contract_status' => $contractStatus,
            'starts_at' => '2026-06-01 00:00:00 UTC',
            'trial_ends_at' => $contractStatus === 'trial' ? '2026-07-01 00:00:00 UTC' : null,
            'ends_at' => $endsAt,
        ]);
    }
}
