<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\Auditable;
use App\Models\User;

class CustomerNote extends Model
{
    use HasFactory, SoftDeletes, HasUuid, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'type',
        'subject',
        'content',
        'interaction_date',
        'duration_minutes',
        'outcome',
        'is_private',
        'is_important',
        'is_pinned',
        'attachments',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'interaction_date' => 'datetime',
        'duration_minutes' => 'integer',
        'is_private' => 'boolean',
        'is_important' => 'boolean',
        'is_pinned' => 'boolean',
        'attachments' => 'array',
    ];

    /**
     * Get the customer that owns the note.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the note.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the note.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if note is private.
     */
    public function isPrivate(): bool
    {
        return $this->is_private;
    }

    /**
     * Check if note is important.
     */
    public function isImportant(): bool
    {
        return $this->is_important;
    }

    /**
     * Check if note is pinned.
     */
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return sprintf('%d hour%s %d minute%s', 
                $hours, $hours > 1 ? 's' : '', 
                $minutes, $minutes > 1 ? 's' : ''
            );
        }

        return sprintf('%d minute%s', $minutes, $minutes > 1 ? 's' : '');
    }
}
