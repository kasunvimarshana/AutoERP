<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Languages;

use Modules\Configuration\Application\Contracts\UseCases\Languages\GetLanguageServiceInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetLanguageService implements GetLanguageServiceInterface
{
    public function __construct(private readonly LanguageRepositoryInterface $languages)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $language = $this->languages->findById($id);
            if ($language === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Language not found.'));
            }

            return Result::success($language);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
