<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Resources;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
final class EmployeeStatusHistoryResource extends JsonResource { public function toArray(Request $request): array { return ['id' => $this->getKey(), 'old_status' => $this->old_status instanceof BackedEnum ? $this->old_status->value : $this->old_status, 'new_status' => $this->new_status instanceof BackedEnum ? $this->new_status->value : $this->new_status, 'reason' => $this->reason, 'changed_by' => $this->changed_by, 'changed_at' => $this->changed_at?->toISOString()]; } }
