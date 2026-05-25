<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Currencies;

use Modules\Configuration\Application\Contracts\UseCases\Currencies\GetCurrencyServiceInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetCurrencyService implements GetCurrencyServiceInterface
{
    public function __construct(private readonly CurrencyRepositoryInterface $currencies)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $currency = $this->currencies->findById($id);
            if ($currency === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Currency not found.'));
            }

            return Result::success($currency);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
