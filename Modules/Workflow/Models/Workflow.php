<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;
use Modules\Workflow\Enums\WorkflowStatus;

class Workflow extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'name',
        'description',
        'code',
        'status',
        'trigger_type',
        'trigger_config',
        'entity_type',
        'entity_id',
        'version',
        'is_template',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => WorkflowStatus::class,
        'trigger_config' => 'array',
        'metadata' => 'array',
        'is_template' => 'boolean',
        'version' => 'integer',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sequence');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canExecute(): bool
    {
        return $this->status->canExecute();
    }

    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    public function activate(): void
    {
        $this->update(['status' => WorkflowStatus::ACTIVE]);
    }

    public function deactivate(): void
    {
        $this->update(['status' => WorkflowStatus::INACTIVE]);
    }

    public function archive(): void
    {
        $this->update(['status' => WorkflowStatus::ARCHIVED]);
    }
}
