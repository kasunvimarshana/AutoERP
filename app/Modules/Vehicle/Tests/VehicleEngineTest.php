<?php

declare(strict_types=1);

namespace Modules\Vehicle\Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerCreationService;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\DTOs\VehicleAttributeData;
use Modules\Vehicle\DTOs\VehicleCategoryData;
use Modules\Vehicle\DTOs\VehicleDocumentData;
use Modules\Vehicle\DTOs\VehicleMakeData;
use Modules\Vehicle\DTOs\VehicleModelData;
use Modules\Vehicle\DTOs\VehicleOwnershipData;
use Modules\Vehicle\DTOs\VehicleStatusChangeData;
use Modules\Vehicle\DTOs\VehicleTypeData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\Vehicle\Models\VehicleMake;
use Modules\Vehicle\Models\VehicleModel;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\Vehicle\Models\VehicleType;
use Modules\Vehicle\Services\VehicleAttributeService;
use Modules\Vehicle\Services\VehicleCategoryService;
use Modules\Vehicle\Services\VehicleCreationService;
use Modules\Vehicle\Services\VehicleDocumentService;
use Modules\Vehicle\Services\VehicleLookupService;
use Modules\Vehicle\Services\VehicleMakeService;
use Modules\Vehicle\Services\VehicleModelService;
use Modules\Vehicle\Services\VehicleOwnershipService;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\Vehicle\Services\VehicleTypeService;
use Tests\TestCase;

final class VehicleEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_creation_builds_reference_graph(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'VEH-CUS');

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
            ownerships: [new VehicleOwnershipData(VehicleOwnershipType::CustomerOwned, now()->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_CUSTOMER, ownerId: (int) $customer->getKey(), isCurrent: true)],
            attributes: [new VehicleAttributeData('body_style', 'Hatchback', VehicleAttributeDataType::Text)],
        ));

        $this->assertSame(VehicleStatus::Active, $vehicle->status);
        $this->assertSame('25.500000', (string) $vehicle->odometer_reading);
        $this->assertCount(1, $vehicle->documents);
        $this->assertCount(1, $vehicle->ownerships);
        $this->assertCount(1, $vehicle->attributes);
        $this->assertCount(1, $vehicle->statusHistories);
        $this->assertSame((int) $customer->getKey(), (int) $vehicle->currentOwnerships()->forOwnerType(VehicleOwnership::OWNER_TYPE_CUSTOMER)->first()?->owner_id);

        $result = app(VehicleLookupService::class)->result($vehicle);
        $this->assertSame('VEH-CREATE-1', $result->vehicleNumber);
    }

    public function test_duplicates_and_make_model_relationship_are_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        $otherMake = app(VehicleMakeService::class)->create(new VehicleMakeData($tenantId, 'OTHER', 'Other', $organizationUnitId));
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

        $document = app(VehicleDocumentService::class)->create($vehicle, new VehicleDocumentData(VehicleDocumentType::Registration, 'REG-DOC'));
        $ownership = app(VehicleOwnershipService::class)->assign($vehicle, new VehicleOwnershipData(VehicleOwnershipType::CustomerOwned, now()->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_CUSTOMER, ownerId: (int) $customer->getKey()));
        $attribute = app(VehicleAttributeService::class)->create($vehicle, new VehicleAttributeData('seat_count', '5', VehicleAttributeDataType::Number));

        $this->assertDatabaseHas('vehicle_documents', ['id' => $document->getKey(), 'document_type' => VehicleDocumentType::Registration->value]);
        $this->assertDatabaseHas('vehicle_ownerships', ['id' => $ownership->getKey(), 'is_current' => true]);
        $this->assertDatabaseHas('vehicle_attributes', ['id' => $attribute->getKey(), 'attribute_key' => 'seat_count']);

        $supplierOwnership = app(VehicleOwnershipService::class)->assign($vehicle, new VehicleOwnershipData(VehicleOwnershipType::ThirdParty, now()->addDay()->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_SUPPLIER, ownerId: $supplierId, isCurrent: true));
        $replacementOwnership = app(VehicleOwnershipService::class)->assign($vehicle, new VehicleOwnershipData(VehicleOwnershipType::CustomerOwned, now()->addDays(2)->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_CUSTOMER, ownerId: (int) $replacementCustomer->getKey(), isCurrent: true));
        $this->assertFalse((bool) $ownership->refresh()->is_current);
        $this->assertTrue((bool) $replacementOwnership->refresh()->is_current);
        $this->assertTrue((bool) $supplierOwnership->refresh()->is_current);
        $this->assertSame(2, $vehicle->ownerships()->where('is_current', true)->count());
        $this->assertSame(1, $vehicle->ownerships()->forOwnerType(VehicleOwnership::OWNER_TYPE_CUSTOMER)->where('is_current', true)->count());

        app(VehicleDocumentService::class)->delete($vehicle, $document);
        app(VehicleAttributeService::class)->delete($vehicle, $attribute);
        $this->assertSoftDeleted('vehicle_documents', ['id' => $document->getKey()]);
        $this->assertDatabaseMissing('vehicle_attributes', ['id' => $attribute->getKey()]);
    }

    public function test_status_history_and_lookup_filters(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-STATUS', (int) $make->getKey(), (int) $model->getKey(), typeId: (int) $type->getKey(), categoryId: (int) $category->getKey());

        app(VehicleStatusService::class)->change($vehicle, new VehicleStatusChangeData(VehicleStatus::UnderService, 'Workshop', 7));

        $this->assertSame(VehicleStatus::UnderService, $vehicle->refresh()->status);
        $this->assertCount(2, $vehicle->statusHistories()->get());
        $this->assertTrue(app(VehicleLookupService::class)->vehiclesAvailableForService($tenantId, $organizationUnitId)->contains($vehicle));
        $this->assertFalse(app(VehicleLookupService::class)->vehiclesAvailableForRental($tenantId, $organizationUnitId)->contains($vehicle));
        $this->assertTrue(app(VehicleLookupService::class)->vehiclesByType($tenantId, (int) $type->getKey(), $organizationUnitId)->contains($vehicle));
        $this->assertTrue(app(VehicleLookupService::class)->vehiclesByCategory($tenantId, (int) $category->getKey(), $organizationUnitId)->contains($vehicle));
    }

    public function test_referenced_master_records_cannot_be_deleted(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $this->vehicle($tenantId, $organizationUnitId, 'VEH-MASTER-REF', (int) $make->getKey(), (int) $model->getKey(), typeId: (int) $type->getKey(), categoryId: (int) $category->getKey());

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
        $authMock = $this->createMock(\Modules\Vehicle\Services\VehicleAuthorizationService::class);
        $authMock->method('assert')->willReturn(null);
        $this->app->instance(\Modules\Vehicle\Services\VehicleAuthorizationService::class, $authMock);
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model, $type, $category] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'API-CUS');

        $create = $this->postJson('/api/v1/vehicles/with-relations', [
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
            'ownerships' => [['ownership_type' => 'customer_owned', 'owner_type' => VehicleOwnership::OWNER_TYPE_CUSTOMER, 'owner_id' => $customer->getKey(), 'started_at' => now()->toDateString(), 'is_current' => true]],
            'attributes' => [['attribute_key' => 'trim', 'attribute_value' => 'G', 'data_type' => 'text']],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.vehicle_number', 'VEH-API')
            ->assertJsonPath('data.make.name', $make->name)
            ->assertJsonPath('data.model.name', $model->name)
            ->assertJsonPath('data.current_ownerships.0.owner.name', $customer->name)
            ->assertJsonPath('data.documents.0.document_type', 'insurance')
            ->assertJsonStructure(['data' => ['id', 'vehicle_number', 'make', 'model', 'current_ownerships', 'documents', 'ownerships', 'attributes']]);

        $id = (int) $create->json('data.id');
        $this->getJson("/api/v1/vehicles/lookup/active?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonFragment(['vehicle_number' => 'VEH-API']);

        $this->putJson("/api/v1/vehicles/{$id}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'color' => 'Blue',
        ])->assertOk()->assertJsonPath('data.color', 'Blue');

        $this->patchJson("/api/v1/vehicles/{$id}/status", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'status' => 'under_service',
            'reason' => 'Inspection',
        ])->assertOk()->assertJsonPath('data.status', 'under_service');

        $this->getJson("/api/v1/vehicles/{$id}/status-history?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_vehicle_document_store_and_update_endpoints_do_not_throw_type_error(): void
    {
        $this->withoutMiddleware();
        $authMock = $this->createMock(\Modules\Vehicle\Services\VehicleAuthorizationService::class);
        $authMock->method('assert')->willReturn(null);
        $this->app->instance(\Modules\Vehicle\Services\VehicleAuthorizationService::class, $authMock);
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);

        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-DOC', (int) $make->getKey(), (int) $model->getKey());
        $id = (int) $vehicle->getKey();

        $uploaded = \Illuminate\Http\Testing\File::create('test.pdf', 1, 'application/pdf');

        $create = $this->post("/api/v1/vehicles/{$id}/documents", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => 'insurance',
            'file' => $uploaded,
        ]);

        $create->assertCreated();
        $docId = (int) $create->json('data.id');

        // metadata-only update should succeed without requiring a file
        $this->putJson("/api/v1/vehicles/{$id}/documents/{$docId}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'notes' => 'Updated notes',
        ])->assertOk()->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_vehicle_attribute_store_endpoint_does_not_throw_type_error(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);

        $vehicle = $this->vehicle($tenantId, $organizationUnitId, 'VEH-ATTR', (int) $make->getKey(), (int) $model->getKey());
        $id = (int) $vehicle->getKey();

        $create = $this->postJson("/api/v1/vehicles/{$id}/attributes", [
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
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        $customer = $this->customer($tenantId, $organizationUnitId, 'FILTER-CUS');
        $supplierId = $this->supplier($tenantId, $organizationUnitId, 'FILTER-SUP');

        app(VehicleCreationService::class)->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            vehicleNumber: 'VEH-CUSTOMER',
            vehicleMakeId: (int) $make->getKey(),
            vehicleModelId: (int) $model->getKey(),
            ownerships: [new VehicleOwnershipData(VehicleOwnershipType::CustomerOwned, now()->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_CUSTOMER, ownerId: (int) $customer->getKey())],
        ));
        app(VehicleCreationService::class)->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            vehicleNumber: 'VEH-SUPPLIER',
            vehicleMakeId: (int) $make->getKey(),
            vehicleModelId: (int) $model->getKey(),
            ownerships: [new VehicleOwnershipData(VehicleOwnershipType::ThirdParty, now()->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_SUPPLIER, ownerId: $supplierId)],
        ));
        app(VehicleCreationService::class)->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            vehicleNumber: 'VEH-COMPANY',
            vehicleMakeId: (int) $make->getKey(),
            vehicleModelId: (int) $model->getKey(),
            ownerships: [new VehicleOwnershipData(VehicleOwnershipType::CompanyOwned, now()->toDateString(), ownerType: VehicleOwnership::OWNER_TYPE_COMPANY)],
        ));

        $scope = [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ];

        $this->getJson('/api/v1/vehicles?'.http_build_query($scope + ['ownership_scope' => 'customer']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vehicle_number', 'VEH-CUSTOMER');

        $this->getJson('/api/v1/vehicles?'.http_build_query($scope + ['ownership_scope' => 'supplier']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vehicle_number', 'VEH-SUPPLIER');

        $this->getJson('/api/v1/vehicles?'.http_build_query($scope + ['ownership_scope' => 'company']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vehicle_number', 'VEH-COMPANY');
    }

    public function test_master_data_api_and_validation_errors(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationUnitId] = $this->scopeContext();

        $make = $this->postJson('/api/v1/vehicle-makes', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'API-MAKE',
            'name' => 'API Make',
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/vehicle-models', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_make_id' => $make['id'],
            'code' => 'API-MODEL',
            'name' => 'API Model',
        ])->assertCreated()->assertJsonPath('data.make.name', 'API Make');

        $this->postJson('/api/v1/vehicles', [
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
        $this->assertDatabaseHas('vehicle_ownerships', ['tenant_id' => $tenantId, 'ownership_type' => 'customer_owned', 'is_current' => true]);
        foreach (['customer_id', 'current_owner_'.'type', 'current_owner_'.'id'] as $removedColumn) {
            $this->assertFalse(Schema::hasColumn('vehicles', $removedColumn));
        }
        $this->assertFalse(Schema::hasColumn('vehicle_ownerships', 'customer_id'));
        $this->assertSame(1, Vehicle::query()->where('tenant_id', $tenantId)->count());
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
        return app(VehicleCreationService::class)->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            vehicleNumber: $number,
            vehicleMakeId: $makeId,
            vehicleModelId: $modelId,
            vehicleTypeId: $typeId,
            vehicleCategoryId: $categoryId,
            registrationNumber: $registration,
            status: VehicleStatus::Active,
        ));
    }

    /**
     * @return array{VehicleMake, VehicleModel, VehicleType, VehicleCategory}
     */
    private function masterData(int $tenantId, ?int $organizationUnitId, string $prefix = 'MASTER'): array
    {
        $make = app(VehicleMakeService::class)->create(new VehicleMakeData($tenantId, $prefix.'-MAKE', $prefix.' Make', $organizationUnitId));
        $model = app(VehicleModelService::class)->create(new VehicleModelData($tenantId, (int) $make->getKey(), $prefix.'-MODEL', $prefix.' Model', $organizationUnitId));
        $type = app(VehicleTypeService::class)->create(new VehicleTypeData($tenantId, $prefix.'-TYPE', $prefix.' Type', $organizationUnitId));
        $category = app(VehicleCategoryService::class)->create(new VehicleCategoryData($tenantId, $prefix.'-CAT', $prefix.' Category', $organizationUnitId));

        return [$make, $model, $type, $category];
    }

    private function customer(int $tenantId, ?int $organizationUnitId, string $code): Customer
    {
        return app(CustomerCreationService::class)->create(new CreateCustomerData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: $code,
            name: 'Customer '.$code,
            customerType: CustomerType::Company,
            status: CustomerStatus::Active,
        ));
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
        $currencyId = (int) DB::table('currencies')->insertGetId([
            'row_version' => 1,
            'code' => 'V'.Str::upper(Str::random(4)),
            'name' => 'Vehicle Currency '.$suffix,
            'symbol' => 'VC',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VEH-'.$suffix,
            'name' => 'Vehicle Tenant '.$suffix,
            'slug' => 'vehicle-tenant-'.Str::lower($suffix).'-'.Str::lower(Str::random(3)),
            'status' => 'active',
            'currency_id' => $currencyId,
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
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
