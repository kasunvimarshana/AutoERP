<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Timezones;

use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetTimezoneService
{
    public function __construct(private readonly TimezoneRepositoryInterface $timezones) {}

    public function execute(int|string $id): Result
    {
        try {
            $timezone = $this->timezones->findById($id);
            if ($timezone === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Timezone not found.'));
            }

            return Result::success($timezone);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
