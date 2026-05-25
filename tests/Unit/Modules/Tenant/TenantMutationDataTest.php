<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Tenant;

use Modules\Tenant\Application\DTOs\TenantMutationData;
use PHPUnit\Framework\TestCase;

final class TenantMutationDataTest extends TestCase
{
    public function testItMapsSchemaAlignedOptionalFields(): void
    {
        $data = TenantMutationData::fromArray([
            'code' => 'acme',
            'name' => 'Acme Inc',
            'logo_path' => 'tenants/logos/acme.png',
            'cross_org_transactions' => true,
            'tenant_plan_id' => 3,
            'currency_id' => 4,
            'trial_ends_at' => '2026-12-31 00:00:00',
            'subscription_ends_at' => '2027-01-31 00:00:00',
            'logo_tmp_path' => 'C:/tmp/acme.png',
            'logo_original_name' => 'acme.png',
        ]);

        $this->assertSame('tenants/logos/acme.png', $data->logoPath);
        $this->assertTrue($data->crossOrgTransactions);
        $this->assertSame(3, $data->tenantPlanId);
        $this->assertSame(4, $data->currencyId);
        $this->assertSame('2026-12-31 00:00:00', $data->trialEndsAt);
        $this->assertSame('2027-01-31 00:00:00', $data->subscriptionEndsAt);
        $this->assertSame('C:/tmp/acme.png', $data->logoTmpPath);
        $this->assertSame('acme.png', $data->logoOriginalName);
    }
}
