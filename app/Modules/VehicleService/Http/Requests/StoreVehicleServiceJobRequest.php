<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobType;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceJobRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        $jobType = VehicleServiceJobType::tryFrom((string) $this->input('type'));
        $tracksMileage = $jobType?->tracksMileage() ?? false;

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(! $this->isMethod('post')),
            'job_number' => ['nullable', 'string', 'max:100'],
            'job_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:job_date'],
            'type' => ['required', Rule::enum(VehicleServiceJobType::class)],
            'customer_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'bill_to_customer_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_employee_id' => ['nullable', 'integer', 'min:1'],
            'supervisor_commission_type' => [
                'nullable',
                'required_with:supervisor_commission_value',
                Rule::enum(VehicleServiceCommissionType::class),
            ],
            'supervisor_commission_value' => [
                'nullable',
                'required_with:supervisor_commission_type',
                'decimal:0,6',
                'min:0',
            ],
            'odometer_reading' => [
                'nullable',
                Rule::requiredIf($tracksMileage),
                Rule::prohibitedIf(! $tracksMileage),
                'decimal:0,6',
                'min:0',
            ],
            'next_service_mileage' => [
                'nullable',
                Rule::prohibitedIf(! $tracksMileage),
                'decimal:0,6',
                'min:0',
            ],
            'manual_job_card' => ['nullable', 'string', 'max:100'],
            'fuel_level' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'customer_complaint' => ['nullable', 'string'],
            'lines' => [$this->isMethod('post') ? 'sometimes' : 'prohibited', 'array'],
            'lines.*' => ['array:line_source_type,item_id,item_variant_id,batch_id,batch_price_revision_id,uom_id,description,quantity,unit_cost,unit_price,discount_calculation_type,discount_rate,discount_amount,tax_calculation_type,tax_rate,tax_amount,charge_calculation_type,charge_rate,charge_amount,is_customer_supplied,is_billable,expand_combo'],
            'lines.*.line_source_type' => ['required', Rule::enum(VehicleServiceLineSourceType::class)],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_price_revision_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.discount_calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'lines.*.discount_rate' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'lines.*.tax_rate' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'lines.*.tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'lines.*.charge_rate' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.is_customer_supplied' => ['nullable', 'boolean'],
            'lines.*.is_billable' => ['nullable', 'boolean'],
            'lines.*.expand_combo' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): VehicleServiceJobData
    {
        return new VehicleServiceJobData(
            tenantId: $this->tenantId(),
            jobDate: (string) $this->input('job_date'),
            customerId: (int) $this->input('customer_id'),
            vehicleId: (int) $this->input('vehicle_id'),
            type: VehicleServiceJobType::from((string) $this->input('type')),
            billToCustomerId: $this->intOrNull('bill_to_customer_id'),
            organizationUnitId: $this->organizationUnitId(),
            jobNumber: $this->stringOrNull('job_number'),
            expectedDeliveryDate: $this->stringOrNull('expected_delivery_date'),
            supervisorEmployeeId: $this->intOrNull('supervisor_employee_id'),
            supervisorCommissionType: $this->filled('supervisor_commission_type')
                ? VehicleServiceCommissionType::from((string) $this->input('supervisor_commission_type'))
                : null,
            supervisorCommissionValue: $this->filled('supervisor_commission_value')
                ? (string) $this->input('supervisor_commission_value')
                : null,
            odometerReading: $this->stringOrNull('odometer_reading'),
            nextServiceMileage: $this->stringOrNull('next_service_mileage'),
            manualJobCard: $this->stringOrNull('manual_job_card'),
            fuelLevel: $this->stringOrNull('fuel_level'),
            priority: $this->stringOrNull('priority'),
            notes: $this->stringOrNull('notes'),
            customerComplaint: $this->stringOrNull('customer_complaint'),
            customerComplaintProvided: $this->has('customer_complaint'),
            createdBy: $this->currentUserId(),
        );
    }

    /** @return list<VehicleServiceLineData> */
    public function lineData(): array
    {
        $lines = [];
        foreach ($this->validated('lines', []) as $line) {
            $lines[] = new VehicleServiceLineData(
                lineSourceType: VehicleServiceLineSourceType::from((string) $line['line_source_type']),
                description: (string) $line['description'],
                quantity: (string) $line['quantity'],
                unitPrice: (string) $line['unit_price'],
                itemId: isset($line['item_id']) ? (int) $line['item_id'] : null,
                itemVariantId: isset($line['item_variant_id']) ? (int) $line['item_variant_id'] : null,
                batchId: isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                batchPriceRevisionId: isset($line['batch_price_revision_id']) ? (int) $line['batch_price_revision_id'] : null,
                uomId: isset($line['uom_id']) ? (int) $line['uom_id'] : null,
                unitCost: (string) ($line['unit_cost'] ?? '0.000000'),
                discountCalculationType: $line['discount_calculation_type'] ?? null,
                discountRate: (string) ($line['discount_rate'] ?? '0.000000'),
                discountAmount: (string) ($line['discount_amount'] ?? '0.000000'),
                taxCalculationType: $line['tax_calculation_type'] ?? null,
                taxRate: (string) ($line['tax_rate'] ?? '0.000000'),
                taxAmount: (string) ($line['tax_amount'] ?? '0.000000'),
                chargeCalculationType: $line['charge_calculation_type'] ?? null,
                chargeRate: (string) ($line['charge_rate'] ?? '0.000000'),
                chargeAmount: (string) ($line['charge_amount'] ?? '0.000000'),
                isCustomerSupplied: (bool) ($line['is_customer_supplied'] ?? false),
                isBillable: isset($line['is_billable']) ? (bool) $line['is_billable'] : null,
                expandCombo: (bool) ($line['expand_combo'] ?? true),
            );
        }

        return $lines;
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
