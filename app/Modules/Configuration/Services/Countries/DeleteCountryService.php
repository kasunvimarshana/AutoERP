<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Countries;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\CountryRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class DeleteCountryService
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    public function execute(int|string $id): Result
    {
        try {
            $existing = $this->countries->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Country not found.'));
            }

            return Result::success($this->countries->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
