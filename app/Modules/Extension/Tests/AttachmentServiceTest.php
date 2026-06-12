<?php

declare(strict_types=1);

namespace Modules\Extension\Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Customer\Models\Customer;
use Modules\Extension\Enums\AttachmentVisibility;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Services\Attachments\AttachmentService;
use Tests\TestCase;

final class AttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_derives_storage_metadata_and_enforces_tenant_and_restricted_access(): void
    {
        [$tenantId, $organizationUnitId, $userId, $customer] = $this->scopeContext();
        Storage::fake('local');
        config()->set('extension.attachments.disk', 'local');
        $this->bindScope($tenantId, $organizationUnitId, $userId);

        $result = app(AttachmentService::class)->create([
            'attachable_type' => 'customer',
            'attachable_id' => (int) $customer->getKey(),
            'category' => 'contract',
            'visibility' => 'restricted',
            'display_name' => 'Signed agreement',
            'metadata' => ['source' => 'test'],
        ], UploadedFile::fake()->createWithContent('agreement.txt', 'signed agreement'));

        $this->assertTrue($result->isSuccess(), $result->error()?->message ?? '');
        $attachment = $result->valueOrFail();
        $this->assertInstanceOf(AttachmentModel::class, $attachment);
        $this->assertSame($tenantId, (int) $attachment->tenant_id);
        $this->assertSame($organizationUnitId, (int) $attachment->organization_unit_id);
        $this->assertSame('customer', $attachment->attachable_type);
        $this->assertSame(AttachmentVisibility::Restricted, $attachment->visibility);
        $this->assertSame('text/plain', $attachment->mime_type);
        $this->assertSame(strlen('signed agreement'), (int) $attachment->size);
        $this->assertSame(hash('sha256', 'signed agreement'), $attachment->checksum_sha256);
        $this->assertStringStartsWith(
            "documents/{$tenantId}/org-{$organizationUnitId}/customer/",
            (string) $attachment->file_path,
        );
        Storage::disk('local')->assertExists((string) $attachment->file_path);

        $this->bindScope($tenantId, $organizationUnitId, $userId + 1000);
        $restricted = app(AttachmentService::class)->get((int) $attachment->getKey());
        $this->assertTrue($restricted->isFailure());

        $this->bindScope($tenantId + 1000, $organizationUnitId, $userId);
        $crossTenant = app(AttachmentService::class)->get((int) $attachment->getKey());
        $this->assertTrue($crossTenant->isFailure());
    }

    public function test_versioning_promotes_one_current_version_and_soft_delete_retains_content(): void
    {
        [$tenantId, $organizationUnitId, $userId, $customer] = $this->scopeContext();
        Storage::fake('local');
        config()->set('extension.attachments.disk', 'local');
        $this->bindScope($tenantId, $organizationUnitId, $userId);

        $created = app(AttachmentService::class)->create([
            'attachable_type' => 'customer',
            'attachable_id' => (int) $customer->getKey(),
            'category' => 'compliance',
        ], UploadedFile::fake()->createWithContent('certificate.txt', 'version one'));
        $this->assertTrue($created->isSuccess(), $created->error()?->message ?? '');
        /** @var AttachmentModel $first */
        $first = $created->valueOrFail();

        $versioned = app(AttachmentService::class)->createVersion(
            (int) $first->getKey(),
            ['display_name' => 'Certificate v2'],
            UploadedFile::fake()->createWithContent('certificate-v2.txt', 'version two'),
        );
        $this->assertTrue($versioned->isSuccess(), $versioned->error()?->message ?? '');
        /** @var AttachmentModel $second */
        $second = $versioned->valueOrFail();

        $this->assertSame((string) $first->version_group_uuid, (string) $second->version_group_uuid);
        $this->assertSame(2, (int) $second->version_number);
        $this->assertSame((int) $first->getKey(), (int) $second->previous_version_id);
        $this->assertFalse((bool) $first->refresh()->is_current);
        $this->assertTrue((bool) $second->is_current);

        $versions = app(AttachmentService::class)->versions((int) $second->getKey());
        $this->assertTrue($versions->isSuccess());
        $this->assertCount(2, $versions->valueOrFail());

        $path = (string) $second->file_path;
        $deleted = app(AttachmentService::class)->delete((int) $second->getKey());
        $this->assertTrue($deleted->isSuccess());
        $this->assertSoftDeleted('attachments', ['id' => $second->getKey(), 'deleted_by' => $userId]);
        Storage::disk('local')->assertExists($path);
    }

    /** @return array{0: int, 1: int, 2: int, 3: Customer} */
    private function scopeContext(): array
    {
        $this->seed(DatabaseSeeder::class);

        $tenantId = (int) DB::table('tenants')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->value('id');
        $userId = (int) DB::table('users')
            ->where('tenant_id', $tenantId)
            ->value('id');
        $customer = Customer::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->firstOrFail();

        return [$tenantId, $organizationUnitId, $userId, $customer];
    }

    private function bindScope(int $tenantId, ?int $organizationUnitId, int $userId): void
    {
        $tenant = Mockery::mock(CurrentTenantContextAccessorInterface::class);
        $tenant->shouldReceive('currentTenantId')->andReturn($tenantId);
        $organizationUnit = Mockery::mock(CurrentOrganizationUnitContextAccessorInterface::class);
        $organizationUnit->shouldReceive('currentOrganizationUnitId')->andReturn($organizationUnitId);
        $user = Mockery::mock(CurrentUserContextAccessorInterface::class);
        $user->shouldReceive('currentUserId')->andReturn($userId);

        $this->app->instance(CurrentTenantContextAccessorInterface::class, $tenant);
        $this->app->instance(CurrentOrganizationUnitContextAccessorInterface::class, $organizationUnit);
        $this->app->instance(CurrentUserContextAccessorInterface::class, $user);
    }
}
