<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use InvalidArgumentException;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantSubscriptionOperation;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionMutationPolicy;
use PHPUnit\Framework\TestCase;

final class TenantSubscriptionMutationPolicyTest extends TestCase
{
    private TenantSubscriptionMutationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TenantSubscriptionMutationPolicy();
    }

    public function test_archived_tenants_are_read_only_for_every_subscription_command(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Archived tenants are read-only');

        $this->policy->assertTenantCanMutate(new DataRecord([
            'id' => 1,
            'status' => 'archived',
        ]));
    }

    public function test_assign_requires_no_pointer_or_a_terminal_pointer_with_matching_version(): void
    {
        self::assertTrue($this->policy->operationAllowed(TenantSubscriptionOperation::ASSIGN, null, null));
        self::assertTrue($this->policy->operationAllowed(
            TenantSubscriptionOperation::ASSIGN,
            $this->pointer('cancelled', 4),
            4,
        ));
        self::assertFalse($this->policy->operationAllowed(
            TenantSubscriptionOperation::ASSIGN,
            $this->pointer('assigned', 4),
            4,
        ));
    }

    public function test_non_assign_commands_require_an_assigned_pointer_with_matching_version(): void
    {
        self::assertTrue($this->policy->operationAllowed(
            TenantSubscriptionOperation::RENEW,
            $this->pointer('assigned', 5),
            5,
        ));
        self::assertFalse($this->policy->operationAllowed(
            TenantSubscriptionOperation::CORRECT,
            $this->pointer('assigned', 5),
            4,
        ));
        self::assertFalse($this->policy->operationAllowed(
            TenantSubscriptionOperation::EXTEND,
            $this->pointer('expired', 5),
            5,
        ));
    }

    private function pointer(string $state, int $version): DataRecord
    {
        return new DataRecord([
            'id' => 10,
            'current_state' => $state,
            'row_version' => $version,
        ]);
    }
}
