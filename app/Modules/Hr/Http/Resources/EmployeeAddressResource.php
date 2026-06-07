<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Resources;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
final class EmployeeAddressResource extends JsonResource { public function toArray(Request $request): array { return ['id' => $this->getKey(), 'address_type' => $this->address_type instanceof BackedEnum ? $this->address_type->value : $this->address_type, 'address_line_1' => $this->address_line_1, 'address_line_2' => $this->address_line_2, 'city' => $this->city, 'state' => $this->state, 'postal_code' => $this->postal_code, 'country' => $this->country, 'is_primary' => (bool) $this->is_primary, 'is_active' => (bool) $this->is_active]; } }
