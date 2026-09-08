<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;

trait BuildsRentalFixture
{
    protected function fixture(): array
    {
        $code = Str::upper(Str::random(10));
        $currency = CurrencyFixture::create(['name' => 'Rental '.$code, 'symbol' => 'R']);
        $tenant = DB::table('tenants')->insertGetId(['uuid' => (string) Str::uuid(), 'code' => $code, 'name' => $code, 'slug' => strtolower($code), 'status' => 'active', 'status_changed_at' => now(), 'base_currency_id' => $currency, 'created_at' => now(), 'updated_at' => now()]);
        $org = OrganizationUnitFixture::create(['tenant_id' => $tenant, 'code' => $code, 'name' => 'Rental '.$code]);
        $actor = TenantUserFixture::create(['tenant_id' => $tenant, 'email' => strtolower($code).'@example.test']);
        $customer = DB::table('customers')->insertGetId(['tenant_id' => $tenant, 'customer_number' => $code, 'code' => $code, 'name' => 'Customer '.$code, 'customer_type' => 'individual', 'status' => 'active']);
        $supplier = DB::table('suppliers')->insertGetId(['tenant_id' => $tenant, 'supplier_number' => $code, 'code' => $code, 'name' => 'Owner '.$code, 'supplier_type' => 'individual', 'status' => 'active']);
        $vehicle = DB::table('vehicles')->insertGetId(['tenant_id' => $tenant, 'vehicle_number' => $code, 'registration_number' => $code, 'status' => 'active']);
        $input = ['reference' => 'AG-'.$code, 'party_id' => $customer, 'currency_id' => $currency, 'agreed_on' => '2026-09-01', 'starts_on' => '2026-09-07', 'ends_on' => null, 'basis' => 'monthly', 'driver_mode' => 'self_drive', 'terms' => ['excess_km_rate' => '90']];

        return [new AgreementContext($tenant, $org, $actor), $input, array_replace($input, ['party_id' => $supplier, 'vehicle_id' => $vehicle, 'terms' => ['excess_km_rate' => '60', 'included_km' => '0']])];
    }

    private function activeFixture(callable $work): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner, $work): void {
            $agreements = app(AgreementService::class);
            $c = $agreements->create(AgreementKind::Customer, $context, $customer);
            $o = $agreements->create(AgreementKind::Owner, $context, $owner);
            $c = $agreements->change(AgreementKind::Customer, $context, $c->id, $c->row_version, AgreementAction::Activate);
            $o = $agreements->change(AgreementKind::Owner, $context, $o->id, $o->row_version, AgreementAction::Activate);
            $input = ['vehicle_id' => $owner['vehicle_id'], 'owner_agreement_id' => $o->id, 'starts_at' => '2026-09-07T09:00:00+05:30', 'ends_at' => '2026-09-08T09:00:00+05:30'];
            $work($context, $c, $o, $input);
        });
    }
}
