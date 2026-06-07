<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Resources;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
final class EmployeeRateResource extends JsonResource { public function toArray(Request $request): array { return ['id' => $this->getKey(), 'rate_type' => $this->rate_type instanceof BackedEnum ? $this->rate_type->value : $this->rate_type, 'amount' => (string) $this->amount, 'currency_id' => $this->currency_id, 'currency' => $this->whenLoaded('currency', fn () => $this->currency ? ['id' => $this->currency->getKey(), 'code' => $this->currency->code, 'name' => $this->currency->name] : null), 'effective_from' => $this->effective_from?->toDateString(), 'effective_to' => $this->effective_to?->toDateString(), 'is_active' => (bool) $this->is_active]; } }
