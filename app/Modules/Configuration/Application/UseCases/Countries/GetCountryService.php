<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Countries;

use Modules\Configuration\Application\Contracts\UseCases\Countries\GetCountryServiceInterface;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetCountryService implements GetCountryServiceInterface
{
    public function __construct(private readonly CountryRepositoryInterface $countries)
    {
    }

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
