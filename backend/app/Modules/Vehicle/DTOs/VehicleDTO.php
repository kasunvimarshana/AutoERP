<?php

namespace App\Modules\Vehicle\DTOs;

class VehicleDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $customer_id = null,
        public ?string $branch_id = null,
        public ?string $vin = null,
        public ?string $registration_number = null,
        public ?string $make = null,
        public ?string $model = null,
        public ?int $year = null,
        public ?string $color = null,
        public ?string $fuel_type = null,
        public ?string $transmission_type = null,
        public ?string $engine_number = null,
        public ?string $chassis_number = null,
        public ?int $mileage = null,
        public ?string $purchase_date = null,
        public ?string $last_service_date = null,
        public ?string $next_service_date = null,
        public ?string $status = 'active',
        public ?string $notes = null,
        public ?array $settings = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            vin: $data['vin'] ?? null,
            registration_number: $data['registration_number'] ?? null,
            make: $data['make'] ?? null,
            model: $data['model'] ?? null,
            year: $data['year'] ?? null,
            color: $data['color'] ?? null,
            fuel_type: $data['fuel_type'] ?? null,
            transmission_type: $data['transmission_type'] ?? null,
            engine_number: $data['engine_number'] ?? null,
            chassis_number: $data['chassis_number'] ?? null,
            mileage: $data['mileage'] ?? null,
            purchase_date: $data['purchase_date'] ?? null,
            last_service_date: $data['last_service_date'] ?? null,
            next_service_date: $data['next_service_date'] ?? null,
            status: $data['status'] ?? 'active',
            notes: $data['notes'] ?? null,
            settings: $data['settings'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'vin' => $this->vin,
            'registration_number' => $this->registration_number,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'fuel_type' => $this->fuel_type,
            'transmission_type' => $this->transmission_type,
            'engine_number' => $this->engine_number,
            'chassis_number' => $this->chassis_number,
            'mileage' => $this->mileage,
            'purchase_date' => $this->purchase_date,
            'last_service_date' => $this->last_service_date,
            'next_service_date' => $this->next_service_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'settings' => $this->settings,
        ], fn($value) => !is_null($value));
    }
}
