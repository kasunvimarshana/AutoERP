<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\DTOs\ConfigurationQueryData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Constants\ConfigurationScope;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Result;
use Throwable;

final class ListConfigurationsService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationRecordMapperInterface $recordMapper,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(ConfigurationQueryData $query): Result
    {
        try {
            $source = $query->source !== null ? $this->domain->normalizeSource($query->source) : null;
            $scope = $this->domain->normalizeScope($query->scope);
            $page = $query->page > 0 ? $query->page : ConfigurationDefaults::DEFAULT_PAGE;
            $perPage = $query->perPage > 0
                ? min($query->perPage, ConfigurationDefaults::MAX_PER_PAGE)
                : ConfigurationDefaults::DEFAULT_PER_PAGE;
            $prefix = $query->prefix !== null ? trim($query->prefix) : null;
            $tenantId = $scope === ConfigurationScope::TENANT
                ? ($query->tenantId ?? $this->tenantContext->currentTenantId())
                : null;

            $records = $this->repository->pageByFilters($prefix, $source, $perPage, $page, $scope, $tenantId);
            $items = [];

            foreach ($records->items as $record) {
                if ($record instanceof DataRecord) {
                    $items[] = $this->recordMapper->toValueData($record);
                }
            }

            return Result::success(new PagedResult($items, $records->total, $records->page, $records->perPage));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                ConfigurationErrorCode::INVALID_VALUE,
                [
                    'scope' => $query->scope,
                    'tenant_id' => $query->tenantId,
                    'organization_unit_id' => $query->organizationUnitId,
                ],
            ));
        }
    }
}
