<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
final class EmployeeContactResource extends JsonResource { public function toArray(Request $request): array { return ['id' => $this->getKey(), 'contact_name' => $this->contact_name, 'relationship' => $this->relationship, 'email' => $this->email, 'phone' => $this->phone, 'mobile' => $this->mobile, 'is_emergency_contact' => (bool) $this->is_emergency_contact, 'is_primary' => (bool) $this->is_primary, 'is_active' => (bool) $this->is_active, 'notes' => $this->notes]; } }
