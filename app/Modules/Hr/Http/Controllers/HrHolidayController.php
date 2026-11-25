<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Models\HrHoliday;
use Modules\Hr\Models\HrHolidayCalendar;

final class HrHolidayController
{
    public function indexCalendars(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'calendar_year' => ['nullable', 'integer', 'between:2000,2100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrHolidayCalendar::query()
            ->where('tenant_id', $request->tenantId())
            ->withCount('holidays')
            ->orderByDesc('calendar_year')
            ->orderBy('name');

        if (! empty($validated['calendar_year'])) {
            $query->where('calendar_year', (int) $validated['calendar_year']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function storeCalendar(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('hr_holiday_calendars', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:100'],
            'calendar_year' => ['required', 'integer', 'between:2000,2100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $calendar = DB::transaction(function () use ($request, $tenantId, $validated): HrHolidayCalendar {
            $isDefault = (bool) ($validated['is_default'] ?? false);
            if ($isDefault) {
                HrHolidayCalendar::query()
                    ->where('tenant_id', $tenantId)
                    ->where('calendar_year', (int) $validated['calendar_year'])
                    ->update(['is_default' => false]);
            }

            return HrHolidayCalendar::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $request->organizationUnitId(),
                'code' => $validated['code'],
                'name' => $validated['name'],
                'calendar_year' => (int) $validated['calendar_year'],
                'is_default' => $isDefault,
                'row_version' => 1,
            ]);
        });

        return response()->json(['data' => $calendar], 201);
    }

    public function showCalendar(TenantScopedRequest $request, int $calendar): JsonResponse
    {
        $model = HrHolidayCalendar::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $calendar)
            ->with(['holidays' => fn ($q) => $q->orderBy('holiday_date')])
            ->firstOrFail();

        return response()->json(['data' => $model]);
    }

    public function updateCalendar(TenantScopedRequest $request, int $calendar): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'code' => ['sometimes', 'required', 'string', 'max:32', Rule::unique('hr_holiday_calendars', 'code')->where('tenant_id', $tenantId)->ignore($calendar)],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'calendar_year' => ['sometimes', 'required', 'integer', 'between:2000,2100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $tenantId, $calendar, $validated, $expectedVersion): ?HrHolidayCalendar {
            $model = HrHolidayCalendar::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $calendar)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            $isDefault = $request->has('is_default') ? (bool) $validated['is_default'] : (bool) $model->is_default;
            $year = $request->has('calendar_year') ? (int) $validated['calendar_year'] : (int) $model->calendar_year;

            if ($isDefault) {
                HrHolidayCalendar::query()
                    ->where('tenant_id', $tenantId)
                    ->where('calendar_year', $year)
                    ->where('id', '!=', $model->getKey())
                    ->update(['is_default' => false]);
            }

            $model->update([
                'row_version' => $model->row_version + 1,
                'code' => $request->has('code') ? (string) $validated['code'] : $model->code,
                'name' => $request->has('name') ? (string) $validated['name'] : $model->name,
                'calendar_year' => $year,
                'is_default' => $isDefault,
            ]);

            return $model->refresh();
        });

        if ($updated === null) {
            return response()->json(['message' => 'Holiday calendar was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyCalendar(TenantScopedRequest $request, int $calendar): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $deleted = DB::transaction(function () use ($request, $calendar, $expectedVersion): bool {
            $model = HrHolidayCalendar::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $calendar)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return false;
            }

            $model->delete();

            return true;
        });

        if (! $deleted) {
            return response()->json(['message' => 'Holiday calendar was changed by someone else. Reload before deleting.'], 409);
        }

        return response()->json(null, 204);
    }

    public function indexHolidays(TenantScopedRequest $request, int $calendar): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        HrHolidayCalendar::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $calendar)
            ->firstOrFail();

        $holidays = HrHoliday::query()
            ->where('tenant_id', $request->tenantId())
            ->where('calendar_id', $calendar)
            ->orderBy('holiday_date')
            ->paginate((int) ($validated['per_page'] ?? 50));

        return response()->json($holidays);
    }

    public function storeHoliday(TenantScopedRequest $request, int $calendar): JsonResponse
    {
        $tenantId = $request->tenantId();
        HrHolidayCalendar::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $calendar)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'holiday_date' => ['required', 'date'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_comp_off_eligible' => ['nullable', 'boolean'],
            'overtime_multiplier' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'description' => ['nullable', 'string'],
        ]);

        $holidayDate = CarbonImmutable::parse($validated['holiday_date'])->toDateString();
        $duplicate = HrHoliday::query()
            ->where('calendar_id', $calendar)
            ->whereDate('holiday_date', $holidayDate)
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'holiday_date' => ['The holiday date has already been taken for this calendar.'],
            ]);
        }

        $holiday = HrHoliday::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $request->organizationUnitId(),
            'calendar_id' => $calendar,
            'name' => $validated['name'],
            'holiday_date' => $holidayDate,
            'is_mandatory' => (bool) ($validated['is_mandatory'] ?? true),
            'is_comp_off_eligible' => (bool) ($validated['is_comp_off_eligible'] ?? true),
            'overtime_multiplier' => (string) ($validated['overtime_multiplier'] ?? '2.00'),
            'description' => $validated['description'] ?? null,
            'row_version' => 1,
        ]);

        return response()->json(['data' => $holiday], 201);
    }

    public function updateHoliday(TenantScopedRequest $request, int $holiday): JsonResponse
    {
        $tenantId = $request->tenantId();
        $existing = HrHoliday::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $holiday)
            ->firstOrFail();

        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'holiday_date' => ['sometimes', 'required', 'date'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_comp_off_eligible' => ['nullable', 'boolean'],
            'overtime_multiplier' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'description' => ['nullable', 'string'],
        ]);

        $normalizedDate = $request->has('holiday_date')
            ? CarbonImmutable::parse((string) $validated['holiday_date'])->toDateString()
            : null;

        if ($normalizedDate !== null) {
            $duplicate = HrHoliday::query()
                ->where('calendar_id', $existing->calendar_id)
                ->where('id', '!=', $existing->id)
                ->whereDate('holiday_date', $normalizedDate)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'holiday_date' => ['The holiday date has already been taken for this calendar.'],
                ]);
            }
        }

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $tenantId, $holiday, $validated, $expectedVersion, $normalizedDate): ?HrHoliday {
            $model = HrHoliday::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $holiday)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            $model->update([
                'row_version' => $model->row_version + 1,
                'name' => $request->has('name') ? (string) $validated['name'] : $model->name,
                'holiday_date' => $normalizedDate ?? $model->holiday_date->toDateString(),
                'is_mandatory' => $request->has('is_mandatory') ? (bool) $validated['is_mandatory'] : (bool) $model->is_mandatory,
                'is_comp_off_eligible' => $request->has('is_comp_off_eligible') ? (bool) $validated['is_comp_off_eligible'] : (bool) $model->is_comp_off_eligible,
                'overtime_multiplier' => $request->has('overtime_multiplier') ? (string) ($validated['overtime_multiplier'] ?? '2.00') : (string) $model->overtime_multiplier,
                'description' => $request->has('description') ? ($validated['description'] ?? null) : $model->description,
            ]);

            return $model->refresh();
        });

        if ($updated === null) {
            return response()->json(['message' => 'Holiday was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyHoliday(TenantScopedRequest $request, int $holiday): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $deleted = DB::transaction(function () use ($request, $holiday, $expectedVersion): bool {
            $model = HrHoliday::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $holiday)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return false;
            }

            $model->delete();

            return true;
        });

        if (! $deleted) {
            return response()->json(['message' => 'Holiday was changed by someone else. Reload before deleting.'], 409);
        }

        return response()->json(null, 204);
    }
}
