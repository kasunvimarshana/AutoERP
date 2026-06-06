<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Currencies;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\CurrencyRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class DeleteCurrencyService
{
    public function __construct(private readonly CurrencyRepositoryInterface $currencies) {}

    public function execute(int|string $id): Result
    {
        try {
            $existing = $this->currencies->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Currency not found.'));
            }

            return Result::success($this->currencies->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
