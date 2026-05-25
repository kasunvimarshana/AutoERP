<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\InvoiceReferences;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\ListInvoiceReferencesServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceDefaults;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class ListInvoiceReferencesService implements ListInvoiceReferencesServiceInterface
{
    public function __construct(private readonly InvoiceReferenceRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : InvoiceDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('invoice.pagination.max_per_page', InvoiceDefaults::MAX_PER_PAGE))
                : (int) config('invoice.pagination.default_per_page', InvoiceDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}