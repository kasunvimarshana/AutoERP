<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Countries;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\CountryRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class GetCountryService
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    public function execute(int|string $id): Result
    {
        try {
            $country = $this->countries->findById($id);
            if ($country === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Country not found.'));
            }

            return Result::success($country);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
