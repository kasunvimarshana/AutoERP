<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Languages;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\LanguageRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class DeleteLanguageService
{
    public function __construct(private readonly LanguageRepositoryInterface $languages) {}

    public function execute(int|string $id): Result
    {
        try {
            $existing = $this->languages->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Language not found.'));
            }

            return Result::success($this->languages->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
