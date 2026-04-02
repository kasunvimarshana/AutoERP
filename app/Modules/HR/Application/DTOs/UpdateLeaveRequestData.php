<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial leave request updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload,
 * enabling safe partial updates without accidentally clearing existing data.
 * The custom toArray() only emits keys that were explicitly supplied, so
 * downstream array_key_exists checks remain valid after serialisation.
 */
class UpdateLeaveRequestData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?string $leave_type = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?string $reason = null;

    public ?string $status = null;

    public ?array $metadata = null;

    /**
     * Track which known property names were present in the source array before
     * delegating to the parent fill logic.
     */
    public function fill(array $data): static
    {
        $known = ['id', 'leave_type', 'start_date', 'end_date', 'reason', 'status', 'metadata'];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    /**
     * Return only the keys that were explicitly provided in the source data.
     */
    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    /**
     * Whether a given field was explicitly included in the source payload.
     */
    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
    }

    public function rules(): array
    {
        return [
            'leave_type' => 'sometimes|required|string|in:annual,sick,personal,maternity,paternity,unpaid,other',
            'start_date' => 'sometimes|required|date',
            'end_date'   => 'sometimes|required|date',
            'reason'     => 'nullable|string',
            'status'     => 'sometimes|required|string|in:pending,approved,rejected,cancelled',
            'metadata'   => 'nullable|array',
        ];
    }
}
