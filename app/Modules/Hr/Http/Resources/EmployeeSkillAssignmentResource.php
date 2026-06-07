<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmployeeSkillAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->getKey(), 'skill_id' => $this->skill_id, 'skill' => $this->whenLoaded('skill', fn () => new HrSkillResource($this->skill)), 'proficiency_level' => $this->proficiency_level instanceof BackedEnum ? $this->proficiency_level->value : $this->proficiency_level, 'years_of_experience' => (string) $this->years_of_experience, 'is_primary' => (bool) $this->is_primary];
    }
}
