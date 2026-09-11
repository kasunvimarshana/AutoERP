<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Enums\RentalBasis;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class BaseRentPreview
{
    private const MONTHS_PER_YEAR = 12;

    // Resource guard for a single interactive preview, not a maximum contract term.
    private const MAX_PREVIEW_YEARS = 10;

    private const DATE_TIMEZONE = 'UTC';

    public function __construct(private readonly AgreementService $agreements, private readonly DecimalMath $math) {}

    public function calculate(AgreementKind $kind, AgreementContext $context, int $id, array $input): array
    {
        $agreement = $this->agreements->find($kind, $context, $id);
        $data = Validator::make($input, [
            'policy' => ['required', Rule::enum(BaseRentPolicy::class)],
            'expected_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'from' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'until' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT, 'after_or_equal:from'],
        ])->validate();
        if ((int) $data['expected_version'] !== $agreement->row_version) {
            throw new ConflictHttpException('This agreement changed. Reload it before calculating.');
        }
        $rate = $agreement->terms['base_rate'] ?? null;
        if ($rate === null) {
            throw ValidationException::withMessages(['base_rate' => ['Record an explicit base rental rate before calculating.']]);
        }
        $from = $this->date($data['from']);
        $until = $this->date($data['until']);
        $anchor = $this->date($agreement->starts_on->format(AgreementFields::DATE_FORMAT));
        if ($from->lessThan($anchor) || ($agreement->ends_on !== null && $until->toDateString() > $agreement->ends_on->toDateString())) {
            throw ValidationException::withMessages(['from' => ['The complete preview period must be covered by this agreement.']]);
        }
        if ($until->greaterThanOrEqualTo($from->addYears(self::MAX_PREVIEW_YEARS))) {
            throw ValidationException::withMessages(['until' => ['Split long previews into periods shorter than '.self::MAX_PREVIEW_YEARS.' years.']]);
        }
        // Civil dates are inclusive. UTC is used only for date arithmetic, not custody conversion.
        $end = $until->addDay();
        $segments = [];
        $total = $this->math->normalize(AgreementFields::ZERO);
        if ($agreement->basis === RentalBasis::Daily) {
            $segments[] = $this->segment($from, $end, $from, $end, $rate, monthly: false);
        } else {
            $offset = ($from->year - $anchor->year) * self::MONTHS_PER_YEAR + $from->month - $anchor->month;
            if ($anchor->addMonthsNoOverflow($offset)->greaterThan($from)) {
                $offset--;
            }
            while (($cycleStart = $anchor->addMonthsNoOverflow($offset))->lessThan($end)) {
                // Always calculate from the original anchor: Jan 31 -> Feb 28 -> Mar 31.
                $cycleEnd = $anchor->addMonthsNoOverflow(++$offset);
                $segments[] = $this->segment($cycleStart->max($from), $cycleEnd->min($end), $cycleStart, $cycleEnd, $rate, monthly: true);
            }
        }
        foreach ($segments as $segment) {
            $total = $this->math->add($total, $segment['amount']);
        }

        return ['agreement' => ['id' => $agreement->id, 'reference' => $agreement->reference, 'version' => $agreement->row_version, 'kind' => $kind->value],
            'currency' => $agreement->currency_code_snapshot, 'policy' => $data['policy'], 'from' => $data['from'], 'until' => $data['until'],
            'basis' => $agreement->basis->value, 'rate' => $rate, 'segments' => $segments, 'base_rent' => $total];
    }

    private function segment(CarbonImmutable $from, CarbonImmutable $end, CarbonImmutable $cycleStart, CarbonImmutable $cycleEnd, string $rate, bool $monthly): array
    {
        $days = (int) $from->diffInDays($end);
        $denominator = $monthly ? (int) $cycleStart->diffInDays($cycleEnd) : 1;
        // Cumulative quantization makes adjacent partial previews reconcile to a full cycle.
        $amount = $monthly
            ? $this->math->sub(
                $this->math->div($this->math->mul($rate, (int) $cycleStart->diffInDays($end)), $denominator),
                $this->math->div($this->math->mul($rate, (int) $cycleStart->diffInDays($from)), $denominator),
            )
            : $this->math->mul($rate, $days);

        return ['from' => $from->toDateString(), 'until' => $end->subDay()->toDateString(),
            'cycle_from' => $monthly ? $cycleStart->toDateString() : null, 'cycle_until' => $monthly ? $cycleEnd->subDay()->toDateString() : null,
            'days' => $days, 'denominator_days' => $denominator, 'amount' => $amount];
    }

    private function date(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!'.AgreementFields::DATE_FORMAT, $date, self::DATE_TIMEZONE);
    }
}
