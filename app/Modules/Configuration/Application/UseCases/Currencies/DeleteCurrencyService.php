<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Currencies;

use Modules\Configuration\Application\Contracts\UseCases\Currencies\DeleteCurrencyServiceInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteCurrencyService implements DeleteCurrencyServiceInterface
{
    public function __construct(private readonly CurrencyRepositoryInterface $currencies)
    {
    }

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
