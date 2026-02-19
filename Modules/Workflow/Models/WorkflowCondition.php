<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Workflow\Enums\ConditionType;

class WorkflowCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'step_id',
        'type',
        'field',
        'operator',
        'value',
        'next_step_id',
        'is_default',
        'sequence',
        'metadata',
    ];

    protected $casts = [
        'type' => ConditionType::class,
        'value' => 'array',
        'metadata' => 'array',
        'is_default' => 'boolean',
        'sequence' => 'integer',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }

    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'next_step_id');
    }

    public function evaluate(array $context): bool
    {
        if ($this->is_default) {
            return true;
        }

        $fieldValue = data_get($context, $this->field);

        return $this->type->evaluate($fieldValue, $this->value);
    }
}
