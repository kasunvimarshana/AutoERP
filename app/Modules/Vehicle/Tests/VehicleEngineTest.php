<?php

declare(strict_types=1);

namespace Modules\Vehicle\Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerCreationService;
use Modules\User\Models\UserModel;
use Modules\Vehicle\Data\CreateVehicleOwnershipData;
use Modules\Vehicle\Data\VersionedVehicleOwnershipCommand;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\DTOs\VehicleAttributeData;
use Modules\Vehicle\DTOs\VehicleCategoryData;
use Modules\Vehicle\DTOs\VehicleDocumentData;
use Modules\Vehicle\DTOs\VehicleMakeData;
use Modules\Vehicle\DTOs\VehicleModelData;
use Modules\Vehicle\DTOs\VehicleStatusChangeData;
use Modules\Vehicle\DTOs\VehicleTypeData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\Vehicle\Models\VehicleMake;
use Modules\Vehicle\Models\VehicleModel;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\Vehicle\Models\VehicleType;
use Modules\Vehicle\Services\Ownership\VehicleOwnershipCommandService;
use Modules\Vehicle\Services\VehicleAttributeService;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleCategoryService;
use Modules\Vehicle\Services\VehicleCreationService;
use Modules\Vehicle\Services\VehicleDocumentService;
use Modules\Vehicle\Services\VehicleLookupService;
use Modules\Vehicle\Services\VehicleMakeService;
use Modules\Vehicle\Services\VehicleModelService;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\Vehicle\Services\VehicleTypeService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class VehicleEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(VehicleAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_vehicle_creation_builds_reference_graph(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'VEH-CUS');

        $this->runInTenant($tenantId, function () use ($tenantId, $organizationUnitId, $make, $model, $type, $category, $customer): void {
            $vehicle = app(VehicleCreationService::class)->create(new CreateVehicleData(
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                vehicleNumber: 'VEH-CREATE-1',
                code: 'VEH-CREATE',
                vehicleMakeId: (int) $make->getKey(),
                vehicleModelId: (int) $model->getKey(),
                vehicleTypeId: (int) $type->getKey(),
                vehicleCategoryId: (int) $category->getKey(),
                registrationNumber: 'CAR-1234',
                chassisNumber: 'CH-1234',
                engineNumber: 'EN-1234',
                vinNumber: 'VIN-1234',
                odometerReading: '25.500000',
                status: VehicleStatus::Active,
                documents: [new VehicleDocumentData(VehicleDocumentType::Insurance, 'INS-1', status: VehicleDocumentStatus::Active)],
                attributes: [new VehicleAttributeData('body_style', 'Hatchback', VehicleAttributeDataType::Text)],
            ));
            $ownership = $this->ownership(
                $vehicle,
                VehicleOwnerType::Customer,
                (int) $customer->getKey(),
                VehicleOwnershipType::CustomerOwned,
                now()->toDateString(),
                $tenantId,
                $organizationUnitId,
            );

            $this->assertSame(VehicleStatus::Active, $vehicle->status);
            $this->assertSame('25.500000', (string) $vehicle->odometer_reading);
            $this->assertCount(1, $vehicle->documents);
            $this->assertCount(1, $vehicle->ownerships()->get());
            $this->assertCount(1, $vehicle->attributes);
            $this->assertCount(1, $vehicle->statusHistories);
            $this->assertSame((int) $customer->getKey(), (int) $ownership->owner_id);
            $this->assertSame($customer->name, $ownership->owner_name_snapshot);
            $this->assertSame(1, $vehicle->currentOwnerships()->forOwnerType(VehicleOwnerType::Customer)->count());

            $result = app(VehicleLookupService::class)->result($vehicle);
            $this->assertSame('VEH-CREATE-1', $result->vehicleNumber);
        });
    }

    public function test_duplicates_and_make_model_relationship_are_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        $otherMake = $this->runInTenant($tenantId, fn (): VehicleMake => app(VehicleMakeService::class)->create(new VehicleMakeData($tenantId, 'OTHER', 'Other', $organizationUnitId)));
        $this->vehicle($tenantId, $organizationUnitId, 'VEH-DUP', (int) $make->getKey(), (int) $model->getKey(), registration: 'DUP-REG');

        try {
            $this->vehicle($tenantId, $organizationUnitId, 'VEH-DUP', (int) $make->getKey(), (int) $model->getKey());
            $this->fail('Expected duplicate vehicle number validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Vehicle number already exists for this tenant.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle model must belong to the selected make.');
        $this->vehicle($tenantId, $organizationUnitId, 'VEH-BAD-MODEL', (int) $otherMake->getKey(), (int) $model->getKey());
    }

    public function test_relation_services_cover_documents_ownerships_and_attributes(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'REL-CUS');
        $replacementCustomer = $this->customer($tenantId, $organizationUnitId, 'REL-CUS-2');
        $supplierId = $this->supplier($tenantId, $organizationUnitId, 'REL-SUP');
        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-REL', (int) $make->getKey(), (int) $model->getKey());

        $this->runInTenant($tenantId, function () use ($tenantId, $organizationUnitId, $vehicle, $customer, $replacementCustomer, $supplierId): void {
            $document = app(VehicleDocumentService::class)->create($vehicle, new VehicleDocumentData(VehicleDocumentType::Registration, 'REG-DOC'));
            $ownership = $this->ownership($vehicle, VehicleOwnerType::Customer, (int) $customer->getKey(), VehicleOwnershipType::CustomerOwned, now()->toDateString(), $tenantId, $organizationUnitId);
            $attribute = app(VehicleAttributeService::class)->create($vehicle, new VehicleAttributeData('seat_count', '5', VehicleAttributeDataType::Number));

            $this->assertDatabaseHas('vehicle_documents', ['id' => $document->getKey(), 'document_type' => VehicleDocumentType::Registration->value]);
            $this->assertDatabaseHas('vehicle_ownerships', ['id' => $ownership->getKey(), 'owner_type' => VehicleOwnerType::Customer->value, 'is_current' => true]);
            $this->assertDatabaseHas('vehicle_attributes', ['id' => $attribute->getKey(), 'attribute_key' => 'seat_count']);

            $supplierOwnership = $this->ownership($vehicle, VehicleOwnerType::Supplier, $supplierId, VehicleOwnershipType::Owned, now()->addDay()->toDateString(), $tenantId, $organizationUnitId);
            $replacementOwnership = $this->ownership($vehicle, VehicleOwnerType::Customer, (int) $replacementCustomer->getKey(), VehicleOwnershipType::CustomerOwned, now()->addDays(2)->toDateString(), $tenantId, $organizationUnitId);

            $this->assertFalse((bool) $ownership->refresh()->is_current);
            $this->assertNotNull($ownership->ended_at);
            $this->assertTrue((bool) $replacementOwnership->refresh()->is_current);
            $this->assertTrue((bool) $supplierOwnership->refresh()->is_current);
            $this->assertSame(1, $vehicle->currentOwnerships()->forOwnerType(VehicleOwnerType::Customer)->count());
            $this->assertSame(1, $vehicle->currentOwnerships()->forOwnerType(VehicleOwnerType::Supplier)->count());

            app(VehicleDocumentService::class)->delete($vehicle, $document);
            app(VehicleAttributeService::class)->delete($vehicle, $attribute);
            $this->assertSoftDeleted('vehicle_documents', ['id' => $document->getKey()]);
            $this->assertDatabaseMissing('vehicle_attributes', ['id' => $attribute->getKey()]);
        });
    }

    public function test_status_history_and_lookup_filters(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-STATUS', (int) $make->getKey(), (int) $model->getKey(), typeId: (int) $type->getKey(), categoryId: (int) $category->getKey());

        $this->runInTenant($tenantId, function () use ($tenantId, $organizationUnitId, $vehicle, $type, $category): void {
            app(VehicleStatusService::class)->change($vehicle, new VehicleStatusChangeData(VehicleStatus::UnderService, 'Workshop', 7));

            $this->assertSame(VehicleStatus::UnderService, $vehicle->refresh()->status);
            $this->assertCount(2, $vehicle->statusHistories()->get());
            $this->assertTrue(app(VehicleLookupService::class)->vehiclesAvailableForService($tenantId, $organizationUnitId)->contains($vehicle));
            $this->assertTrue(app(VehicleLookupService::class)->vehiclesByType($tenantId, (int) $type->getKey(), $organizationUnitId)->contains($vehicle));
            $this->assertTrue(app(VehicleLookupService::class)->vehiclesByCategory($tenantId, (int) $category->getKey(), $organizationUnitId)->contains($vehicle));
        });
    }

    public function test_referenced_master_records_cannot_be_deleted(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $this->vehicle($tenantId, $organizationUnitId, 'VEH-MASTER-REF', (int) $make->getKey(), (int) $model->getKey(), typeId: (int) $type->getKey(), categoryId: (int) $category->getKey());

        $this->runInTenant($tenantId, function () use ($make, $model, $type, $category): void {
            foreach ([
                fn () => app(VehicleMakeService::class)->delete($make),
                fn () => app(VehicleModelService::class)->delete($model),
                fn () => app(VehicleTypeService::class)->delete($type),
                fn () => app(VehicleCategoryService::class)->delete($category),
            ] as $deleteReferencedRecord) {
                try {
                    $deleteReferencedRecord();
                    $this->fail('Expected referenced vehicle master data delete to fail.');
                } catch (InvalidArgumentException) {
                    $this->assertTrue(true);
                }
            }
        });

        $this->assertDatabaseHas('vehicle_makes', ['id' => $make->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('vehicle_models', ['id' => $model->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('vehicle_types', ['id' => $type->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('vehicle_categories', ['id' => $category->getKey(), 'deleted_at' => null]);
    }

    public function test_cross_tenant_and_organization_references_are_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scopeContext('OTHER');
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        [$otherMake] = $this->masterData($otherTenantId, $otherOrganizationUnitId, 'OTHER');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle reference belongs to a different tenant.');
        $this->vehicle($tenantId, $organizationUnitId, 'VEH-CROSS', (int) $otherMake->getKey(), (int) $model->getKey());
    }

    public function test_vehicle_api_crud_relations_lookup_and_readable_response(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'API-CUS');

        $create = $this->tenantPostJson($tenantId, '/api/v1/vehicles/with-relations', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle' => [
                'vehicle_number' => 'VEH-API',
                'vehicle_make_id' => $make->getKey(),
                'vehicle_model_id' => $model->getKey(),
                'vehicle_type_id' => $type->getKey(),
                'vehicle_category_id' => $category->getKey(),
                'registration_number' => 'API-1234',
                'status' => 'active',
            ],
            'documents' => [['document_type' => 'insurance', 'document_number' => 'INS-API']],
            'attributes' => [['attribute_key' => 'trim', 'attribute_value' => 'G', 'data_type' => 'text']],
            'ownerships' => [[
                'owner_type' => 'customer',
                'owner_id' => (int) $customer->getKey(),
                'ownership_type' => 'customer_owned',
                'started_at' => now()->toDateString(),
                'is_current' => true,
            ]],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.vehicle_number', 'VEH-API')
            ->assertJsonPath('data.make.name', $make->name)
            ->assertJsonPath('data.model.name', $model->name)
            ->assertJsonPath('data.documents.0.document_type', 'insurance')
            ->assertJsonPath('data.current_customer.name', $customer->name)
            ->assertJsonPath('data.current_ownerships.0.owner.name', $customer->name)
            ->assertJsonStructure(['data' => ['id', 'row_version', 'vehicle_number', 'make', 'model', 'current_ownerships', 'documents', 'ownerships', 'attributes']]);

        $id = (int) $create->json('data.id');
        $rowVersion = (int) $create->json('data.row_version');
        $this->assertGreaterThanOrEqual(0, $rowVersion);

        $this->tenantGetJson($tenantId, "/api/v1/vehicles/{$id}?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonPath('data.current_customer.name', $customer->name)
            ->assertJsonPath('data.current_ownerships.0.owner.name', $customer->name);
        $this->tenantGetJson($tenantId, "/api/v1/vehicles/lookup/active?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonFragment(['vehicle_number' => 'VEH-API']);

        $this->runInTenant($tenantId, fn () => $this->putJson("/api/v1/vehicles/{$id}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => $rowVersion,
            'color' => 'Blue',
        ]))->assertOk()
            ->assertJsonPath('data.color', 'Blue')
            ->assertJsonPath('data.row_version', $rowVersion + 1);

        $this->runInTenant($tenantId, fn () => $this->putJson("/api/v1/vehicles/{$id}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => $rowVersion,
            'color' => 'Red',
        ]))->assertStatus(409)
            ->assertJsonPath('message', 'Vehicle was changed by someone else. Reload before saving.');

        $this->tenantPatchJson($tenantId, "/api/v1/vehicles/{$id}/status", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'status' => 'under_service',
            'reason' => 'Inspection',
        ])->assertOk()->assertJsonPath('data.status', 'under_service');

        $this->tenantGetJson($tenantId, "/api/v1/vehicles/{$id}/status-history?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_vehicle_code_is_generated_visible_editable_and_manufacture_year_is_persisted(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);

        $generatedCode = $this->tenantPostJson($tenantId, '/api/v1/vehicles/code-reservations', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ])->assertOk()->json('data.code');

        $this->assertIsString($generatedCode);
        $this->assertMatchesRegularExpression('/^VEH-\d{6}$/', $generatedCode);

        $created = $this->tenantPostJson($tenantId, '/api/v1/vehicles', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $generatedCode,
            'registration_number' => 'REG-2024',
            'manufacture_year' => 2024,
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.code', $generatedCode)
            ->assertJsonPath('data.registration_number', 'REG-2024')
            ->assertJsonPath('data.manufacture_year', 2024)
            ->assertJsonPath('data.odometer_reading', '0.000000');

        $this->assertMatchesRegularExpression('/^VEH-\d{6}$/', (string) $created->json('data.vehicle_number'));

        $this->tenantPostJson($tenantId, '/api/v1/vehicles', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'CUSTOM-VEHICLE-CODE',
            'registration_number' => 'REG-CUSTOM',
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.code', 'CUSTOM-VEHICLE-CODE');

        $fallbackCode = $this->tenantPostJson($tenantId, '/api/v1/vehicles', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'registration_number' => 'REG-FALLBACK',
            'status' => 'active',
        ])->assertCreated()->json('data.code');

        $this->assertIsString($fallbackCode);
        $this->assertMatchesRegularExpression('/^VEH-\d{6}$/', $fallbackCode);
        $this->assertNotSame($generatedCode, $fallbackCode);
    }

    public function test_vehicle_document_store_and_update_endpoints_do_not_throw_type_error(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);

        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-DOC', (int) $make->getKey(), (int) $model->getKey());
        $id = (int) $vehicle->getKey();

        $uploaded = File::createWithContent('test.pdf', '%PDF-1.4 test document');

        $create = $this->runInTenant($tenantId, fn () => $this->post("/api/v1/vehicles/{$id}/documents", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => 'insurance',
            'file' => $uploaded,
        ]));

        $create->assertCreated();
        $docId = (int) $create->json('data.id');

        // metadata-only update should succeed without requiring a file
        $this->runInTenant($tenantId, fn () => $this->putJson("/api/v1/vehicles/{$id}/documents/{$docId}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => 'insurance',
            'notes' => 'Updated notes',
        ]))->assertOk()->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_vehicle_attribute_store_endpoint_does_not_throw_type_error(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);

        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-ATTR', (int) $make->getKey(), (int) $model->getKey());
        $id = (int) $vehicle->getKey();

        $create = $this->tenantPostJson($tenantId, "/api/v1/vehicles/{$id}/attributes", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'attribute_key' => 'load_capacity',
            'attribute_value' => '1000',
            'data_type' => 'number',
        ]);

        $create->assertCreated()->assertJsonPath('data.attribute_key', 'load_capacity');
    }

    public function test_vehicle_api_exposes_secure_customer_and_supplier_filtered_views(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'FILTER-CUS');
        $supplierId = $this->supplier($tenantId, $organizationUnitId, 'FILTER-SUP');

        $customerVehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-CUSTOMER', (int) $make->getKey(), (int) $model->getKey());
        $this->ownership($customerVehicle, VehicleOwnerType::Customer, (int) $customer->getKey(), VehicleOwnershipType::CustomerOwned, now()->toDateString(), $tenantId, $organizationUnitId);

        $supplierVehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-SUPPLIER', (int) $make->getKey(), (int) $model->getKey());
        $this->ownership($supplierVehicle, VehicleOwnerType::Supplier, $supplierId, VehicleOwnershipType::Owned, now()->toDateString(), $tenantId, $organizationUnitId);

        $companyVehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-COMPANY', (int) $make->getKey(), (int) $model->getKey());
        $this->ownership($companyVehicle, VehicleOwnerType::Company, null, VehicleOwnershipType::CompanyOwned, now()->toDateString(), $tenantId, $organizationUnitId);

        $scope = ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId];
        foreach ([
            'customer' => 'VEH-CUSTOMER',
            'supplier' => 'VEH-SUPPLIER',
            'company' => 'VEH-COMPANY',
        ] as $ownerType => $vehicleNumber) {
            $this->tenantGetJson($tenantId, '/api/v1/vehicles?'.http_build_query($scope + ['ownership_scope' => $ownerType]))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.vehicle_number', $vehicleNumber);
        }
    }

    public function test_vehicle_ownership_service_enforces_periods_active_pairs_current_roles_and_versions(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId, 'REL');
        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-REL', (int) $make->getKey(), (int) $model->getKey());
        $startedAt = now()->startOfDay()->toImmutable();

        $firstCustomer = $this->customer($tenantId, $organizationUnitId, 'REL-CUS-1');
        $secondCustomer = $this->customer($tenantId, $organizationUnitId, 'REL-CUS-2');
        $invalidDateCustomer = $this->customer($tenantId, $organizationUnitId, 'REL-CUS-3');

        $first = $this->ownership($vehicle, VehicleOwnerType::Customer, (int) $firstCustomer->getKey(), VehicleOwnershipType::CustomerOwned, $startedAt->toDateString(), $tenantId, $organizationUnitId);
        $second = $this->ownership($vehicle, VehicleOwnerType::Customer, (int) $secondCustomer->getKey(), VehicleOwnershipType::CustomerOwned, $startedAt->addDay()->toDateString(), $tenantId, $organizationUnitId);

        $this->assertFalse((bool) $first->refresh()->is_current);
        $this->assertNull($first->current_guard);
        $this->assertNull($first->active_guard);
        $this->assertTrue((bool) $second->refresh()->is_current);
        $this->assertSame(1, (int) $second->current_guard);
        $this->assertSame(1, (int) $second->active_guard);

        try {
            $this->ownership($vehicle, VehicleOwnerType::Customer, (int) $secondCustomer->getKey(), VehicleOwnershipType::CustomerOwned, $startedAt->addDays(2)->toDateString(), $tenantId, $organizationUnitId, false);
            $this->fail('Expected duplicate active owner pair validation to fail.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame('An active relationship already exists for this vehicle and owner.', $exception->getMessage());
        }

        try {
            $this->ownership($vehicle, VehicleOwnerType::Customer, (int) $invalidDateCustomer->getKey(), VehicleOwnershipType::CustomerOwned, $startedAt->addDays(3)->toDateString(), $tenantId, $organizationUnitId, false, $startedAt->addDays(2)->toDateString());
            $this->fail('Expected invalid ownership period to fail.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame('Ownership end date must be after its start date.', $exception->getMessage());
        }

        $service = app(VehicleOwnershipCommandService::class);
        $ended = $this->runInTenant($tenantId, function () use ($service, $second, $startedAt): VehicleOwnership {
            $second = $second->refresh();

            return $service->end($second, new VersionedVehicleOwnershipCommand(
                expectedVersion: (int) $second->row_version,
                endedAt: $startedAt->addDays(2)->toDateTimeString(),
            ));
        });
        $this->assertFalse((bool) $ended->is_current);
        $this->assertNull($ended->current_guard);
        $this->assertNull($ended->active_guard);

        $supplierId = $this->supplier($tenantId, $organizationUnitId, 'REL-SUP-1');
        $supplier = $this->ownership($vehicle, VehicleOwnerType::Supplier, $supplierId, VehicleOwnershipType::Owned, $startedAt->toDateString(), $tenantId, $organizationUnitId);
        $this->assertTrue((bool) $supplier->is_current);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Vehicle ownership was changed by another request. Reload and try again.');
        $this->runInTenant($tenantId, fn () => $service->clearCurrent($supplier, new VersionedVehicleOwnershipCommand(expectedVersion: 999)));
    }

    public function test_master_data_api_and_validation_errors(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);

        $make = $this->tenantPostJson($tenantId, '/api/v1/vehicle-makes', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'API-MAKE',
            'name' => 'API Make',
        ])->assertCreated()->json('data');

        $this->tenantPostJson($tenantId, '/api/v1/vehicle-models', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_make_id' => $make['id'],
            'code' => 'API-MODEL',
            'name' => 'API Model',
        ])->assertCreated()->assertJsonPath('data.make.name', 'API Make');

        $this->tenantPostJson($tenantId, '/api/v1/vehicles', [
            'tenant_id' => $tenantId,
            'vehicle_number' => 'BAD-ODO',
            'odometer_reading' => '-1.000000',
            'status' => 'not-valid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['odometer_reading', 'status']);
    }

    public function test_database_seeder_adds_vehicle_master_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');

        $this->assertDatabaseHas('vehicle_makes', ['tenant_id' => $tenantId, 'code' => 'TOYOTA']);
        $this->assertDatabaseHas('vehicle_types', ['tenant_id' => $tenantId, 'code' => 'CAR']);
        $this->assertDatabaseHas('vehicle_categories', ['tenant_id' => $tenantId, 'code' => 'CUSTOMER']);
        $this->assertDatabaseHas('vehicles', ['tenant_id' => $tenantId, 'vehicle_number' => 'VEH-000001']);
        foreach (['customer_id', 'current_owner_'.'type', 'current_owner_'.'id'] as $removedColumn) {
            $this->assertFalse(Schema::hasColumn('vehicles', $removedColumn));
        }
        $this->assertFalse(Schema::hasTable('customer_vehicles'));
        $this->assertFalse(Schema::hasTable('supplier_vehicles'));
        $this->assertSame(1, DB::table('vehicles')->where('tenant_id', $tenantId)->count());
    }

    private function actingAsTenantUser(int $tenantId): void
    {
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Vehicle',
            'last_name' => 'Tester',
            'email' => 'vehicle-'.Str::lower(Str::random(8)).'@example.test',
            'password' => 'secret-password',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->runInTenant($tenantId, fn (): UserModel => UserModel::query()->findOrFail($userId)));
    }

    private function ownership(
        Vehicle $vehicle,
        VehicleOwnerType $ownerType,
        ?int $ownerId,
        VehicleOwnershipType $ownershipType,
        string $startedAt,
        int $tenantId,
        ?int $organizationUnitId,
        bool $isCurrent = true,
        ?string $endedAt = null,
    ): VehicleOwnership {
        return $this->runInTenant($tenantId, fn (): VehicleOwnership => app(VehicleOwnershipCommandService::class)->create(new CreateVehicleOwnershipData(
            vehicleId: (int) $vehicle->getKey(),
            ownerType: $ownerType,
            ownerId: $ownerId,
            ownershipType: $ownershipType,
            startedAt: $startedAt,
            endedAt: $endedAt,
            isCurrent: $isCurrent,
            notes: null,
        ), $tenantId, $organizationUnitId));
    }

    private function vehicle(
        int $tenantId,
        ?int $organizationUnitId,
        string $number,
        ?int $makeId,
        ?int $modelId,
        ?string $registration = null,
        ?int $typeId = null,
        ?int $categoryId = null,
    ): Vehicle {
        return $this->runInTenant($tenantId, fn (): Vehicle => app(VehicleCreationService::class)->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            vehicleNumber: $number,
            vehicleMakeId: $makeId,
            vehicleModelId: $modelId,
            vehicleTypeId: $typeId,
            vehicleCategoryId: $categoryId,
            registrationNumber: $registration,
            status: VehicleStatus::Active,
        )));
    }

    /**
     * @return array{VehicleMake, VehicleModel, VehicleType, VehicleCategory}
     */
    private function masterData(int $tenantId, ?int $organizationUnitId, string $prefix = 'MASTER'): array
    {
        return $this->runInTenant($tenantId, function () use ($tenantId, $organizationUnitId, $prefix): array {
            $make = app(VehicleMakeService::class)->create(new VehicleMakeData($tenantId, $prefix.'-MAKE', $prefix.' Make', $organizationUnitId));
            $model = app(VehicleModelService::class)->create(new VehicleModelData($tenantId, (int) $make->getKey(), $prefix.'-MODEL', $prefix.' Model', $organizationUnitId));
            $type = app(VehicleTypeService::class)->create(new VehicleTypeData($tenantId, $prefix.'-TYPE', $prefix.' Type', $organizationUnitId));
            $category = app(VehicleCategoryService::class)->create(new VehicleCategoryData($tenantId, $prefix.'-CAT', $prefix.' Category', $organizationUnitId));

            return [$make, $model, $type, $category];
        });
    }

    private function customer(int $tenantId, ?int $organizationUnitId, string $code): Customer
    {
        return $this->runInTenant($tenantId, fn (): Customer => app(CustomerCreationService::class)->create(new CreateCustomerData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: $code,
            name: 'Customer '.$code,
            customerType: CustomerType::Company,
            status: CustomerStatus::Active,
        )));
    }

    private function runInTenant(int $tenantId, callable $callback): mixed
    {
        return $this->withTenantExecutionContext($tenantId, $callback);
    }

    private function supplier(int $tenantId, ?int $organizationUnitId, string $code): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'display_name' => 'Supplier '.$code,
            'supplier_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{int, int, int}
     */
    private function scopeContext(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));
        $currencyId = CurrencyFixture::create([
            'name' => 'Vehicle Currency '.$suffix,
            'symbol' => 'VC',
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VEH-'.$suffix,
            'name' => 'Vehicle Tenant '.$suffix,
            'slug' => 'vehicle-tenant-'.Str::lower($suffix).'-'.Str::lower(Str::random(3)),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now()]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$suffix,
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId, $currencyId];
    }
}
