<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Timezones;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\TimezoneRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
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
