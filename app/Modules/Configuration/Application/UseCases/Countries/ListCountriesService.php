<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Countries;

use Modules\Configuration\Application\Contracts\UseCases\Countries\ListCountriesServiceInterface;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class ListCountriesService implements ListCountriesServiceInterface
{
    public function __construct(private readonly CountryRepositoryInterface $countries)
    {
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : ConfigurationDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, ConfigurationDefaults::MAX_PER_PAGE)
                : ConfigurationDefaults::DEFAULT_PER_PAGE;

            return Result::success($this->countries->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
