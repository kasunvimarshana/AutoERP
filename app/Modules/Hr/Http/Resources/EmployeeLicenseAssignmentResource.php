<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Resources;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
final class EmployeeLicenseAssignmentResource extends JsonResource { public function toArray(Request $request): array { return ['id' => $this->getKey(), 'license_id' => $this->license_id, 'license' => $this->whenLoaded('license', fn () => new HrLicenseResource($this->license)), 'license_number' => $this->license_number, 'issued_date' => $this->issued_date?->toDateString(), 'expiry_date' => $this->expiry_date?->toDateString(), 'status' => $this->status instanceof BackedEnum ? $this->status->value : $this->status]; } }
