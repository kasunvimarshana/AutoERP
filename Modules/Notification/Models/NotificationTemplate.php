<?php

declare(strict_types=1);

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Notification Template Model
 *
 * Represents a reusable notification template with variables
 */
class NotificationTemplate extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'code',
        'name',
        'description',
        'type',
        'subject',
        'body_text',
        'body_html',
        'variables',
        'default_data',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'variables' => 'array',
        'default_data' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get all notifications using this template
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'template_id');
    }

    /**
     * Render template with data
     */
    public function render(array $data = []): array
    {
        $mergedData = array_merge($this->default_data ?? [], $data);

        return [
            'subject' => $this->renderString($this->subject, $mergedData),
            'body_text' => $this->renderString($this->body_text, $mergedData),
            'body_html' => $this->renderString($this->body_html, $mergedData),
        ];
    }

    /**
     * Render a string with variables
     */
    protected function renderString(?string $template, array $data): ?string
    {
        if ($template === null) {
            return null;
        }

        $rendered = $template;

        foreach ($data as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $rendered = str_replace($placeholder, (string) $value, $rendered);
        }

        return $rendered;
    }

    /**
     * Validate template variables
     */
    public function validateData(array $data): bool
    {
        if (empty($this->variables)) {
            return true;
        }

        foreach ($this->variables as $variable) {
            if (($variable['required'] ?? false) && ! isset($data[$variable['name']])) {
                return false;
            }
        }

        return true;
    }
}
