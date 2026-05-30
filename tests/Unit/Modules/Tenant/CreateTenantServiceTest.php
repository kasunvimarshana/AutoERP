<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Tenant;

use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\DTOs\TenantValueData;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\UseCases\CreateTenantService;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use PHPUnit\Framework\TestCase;

final class CreateTenantServiceTest extends TestCase
{
    public function testItStoresUploadedLogoAndPersistsLogoPath(): void
    {
        $repository = $this->createMock(TenantRepositoryInterface::class);
        $domain = $this->createMock(TenantDomainServiceInterface::class);
        $mapper = $this->createMock(TenantRecordMapperInterface::class);
        $slugger = $this->createMock(SlugGeneratorInterface::class);
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $files = $this->createMock(FileStorageServiceInterface::class);
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $errorNormalizer = $this->createMock(ErrorNormalizerInterface::class);

        $transactions->method('runInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $repository->method('findByCode')->willReturn(null);
        $repository->method('findByIsolationKey')->willReturn(null);

        $slugger->method('generate')->willReturn('acme');
        $uuidGenerator->method('generate')->willReturnOnConsecutiveCalls('logo-uuid', 'tenant-uuid');

        $domain->method('normalizeCode')->willReturn('ACME');
        $domain->method('normalizeName')->willReturn('Acme Inc');
        $domain->method('normalizeStatus')->willReturn('active');
        $domain->method('ensureIsolationKey')->willReturn('tenant:acme');
        $domain->method('deriveActiveFlag')->willReturn(true);
        $domain->method('normalizeOptionalText')->willReturnCallback(static fn (?string $value): ?string => $value);
        $domain->method('normalizeMetadata')->willReturn([]);

        $files
            ->expects($this->once())
            ->method('store')
            ->with('C:/tmp/logo.png', 'tenants/logos', 'acme-logo-uuid.png')
            ->willReturn('tenants/logos/acme-logo-uuid.png');

        $repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $attributes): bool {
                return ($attributes['logo_path'] ?? null) === 'tenants/logos/acme-logo-uuid.png'
                    && ($attributes['tenant_plan_id'] ?? null) === 2
                    && ($attributes['currency_id'] ?? null) === 1
                    && ($attributes['cross_org_transactions'] ?? null) === true;
            }))
            ->willReturn(new DataRecord([
                'id' => 9,
                'uuid' => 'tenant-uuid',
                'code' => 'ACME',
                'name' => 'Acme Inc',
                'slug' => 'acme',
                'status' => 'active',
                'is_active' => true,
                'is_isolated' => true,
                'isolation_key' => 'tenant:acme',
                'configuration_scope' => 'acme',
                'metadata' => [],
            ]));

        $mapper
            ->method('toValueData')
            ->willReturn(new TenantValueData(
                9,
                'tenant-uuid',
                'ACME',
                'Acme Inc',
                'acme',
                'tenants/logos/acme-logo-uuid.png',
                true,
                2,
                1,
                'active',
                null,
                null,
                true,
                true,
                'tenant:acme',
                'acme',
                [],
            ));

        $service = new CreateTenantService(
            $repository,
            $domain,
            $mapper,
            $slugger,
            $uuidGenerator,
            $files,
            $transactions,
            $errorNormalizer,
        );

        $result = $service->execute([
            'code' => 'acme',
            'name' => 'Acme Inc',
            'logo_tmp_path' => 'C:/tmp/logo.png',
            'logo_original_name' => 'logo.png',
            'tenant_plan_id' => 2,
            'currency_id' => 1,
            'cross_org_transactions' => true,
        ]);

        $this->assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected successful result.',
        );
    }
}
