<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\Contracts\UseCases\ListConfigurationsServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationQueryData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class ListConfigurationsService implements ListConfigurationsServiceInterface
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationRecordMapperInterface $recordMapper,
    ) {
    }

    public function execute(ConfigurationQueryData $query): Result
    {
        try {
            $source = $query->source !== null ? $this->domain->normalizeSource($query->source) : null;
            $page = $query->page > 0 ? $query->page : ConfigurationDefaults::DEFAULT_PAGE;
            $perPage = $query->perPage > 0
                ? min($query->perPage, ConfigurationDefaults::MAX_PER_PAGE)
                : ConfigurationDefaults::DEFAULT_PER_PAGE;
            $prefix = $query->prefix !== null ? trim($query->prefix) : null;

            $records = $this->repository->pageByFilters($prefix, $source, $perPage, $page);
            $items = [];

            foreach ($records->items as $record) {
                if ($record instanceof DataRecord) {
                    $items[] = $this->recordMapper->toValueData($record);
                }
            }

            return Result::success(new PagedResult($items, $records->total, $records->page, $records->perPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
