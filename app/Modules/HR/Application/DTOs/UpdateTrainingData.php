<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial training updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload.
 */
class UpdateTrainingData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $tenant_id = null;

    public ?string $title = null;

    public ?string $start_date = null;

    public ?string $description = null;

    public ?string $trainer = null;

    public ?string $location = null;

    public ?string $end_date = null;

    public ?int $max_participants = null;

    public ?string $status = null;

    public ?array $metadata = null;

    public ?bool $is_active = null;

    public function fill(array $data): static
    {
        $known = [
            'id', 'tenant_id', 'title', 'start_date', 'description', 'trainer',
            'location', 'end_date', 'max_participants', 'status', 'metadata', 'is_active',
        ];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
    }

    public function rules(): array
    {
        return [
            'title'            => 'sometimes|required|string|max:255',
            'start_date'       => 'sometimes|required|date_format:Y-m-d',
            'description'      => 'nullable|string',
            'trainer'          => 'nullable|string|max:255',
            'location'         => 'nullable|string|max:255',
            'end_date'         => 'nullable|date_format:Y-m-d',
            'max_participants' => 'nullable|integer|min:1',
            'status'           => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
            'metadata'         => 'nullable|array',
            'is_active'        => 'nullable|boolean',
        ];
    }
}
