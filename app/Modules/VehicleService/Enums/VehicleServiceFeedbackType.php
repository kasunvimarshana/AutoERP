<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceFeedbackType: string
{
    case Review = 'review';
    case InternalNote = 'internal_note';
    case CustomerFeedback = 'customer_feedback';
    case Complaint = 'complaint';

    public function label(): string
    {
        return match ($this) {
            self::Review => 'Customer Review',
            self::InternalNote => 'Internal Note',
            self::CustomerFeedback => 'Customer Feedback',
            self::Complaint => 'Customer Complaint',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
