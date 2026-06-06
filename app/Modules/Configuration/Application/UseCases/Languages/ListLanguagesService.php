<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Languages;

use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class ListLanguagesService
{
    public function __construct(private readonly LanguageRepositoryInterface $languages) {}

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : ConfigurationDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, ConfigurationDefaults::MAX_PER_PAGE)
                : ConfigurationDefaults::DEFAULT_PER_PAGE;

            return Result::success($this->languages->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
