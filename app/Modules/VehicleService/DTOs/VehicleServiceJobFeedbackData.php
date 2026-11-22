<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\DTOs;

use Modules\VehicleService\Enums\VehicleServiceFeedbackType;

final readonly class VehicleServiceJobFeedbackData
{
    public function __construct(
        public ?VehicleServiceFeedbackType $feedbackType = null,
        public ?string $comment = null,
        public ?int $rating = null,
        public ?string $title = null,
        public ?bool $isCustomerVisible = null,
        public ?string $authorName = null,
        public ?int $userId = null,
        public ?int $expectedVersion = null,
        public ?int $expectedJobVersion = null,
    ) {}
}
