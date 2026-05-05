<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reservation\Domain\ValueObjects\ReservationStatus;

class ChangeReservationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_map(static fn (ReservationStatus $status): string => $status->value, ReservationStatus::cases()));

        return [
            'status' => ['required', 'string', "in:{$statuses}"],
        ];
    }
}
