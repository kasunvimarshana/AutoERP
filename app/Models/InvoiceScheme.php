<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Configurable invoice / reference-number scheme per tenant.
 *
 * Generates formatted reference numbers such as:
 *   INV-00042  (prefix=INV-, start=1, digits=5)
 *   PO-2024-001 (with custom prefix)
 */
class InvoiceScheme extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'scheme_type',
        'prefix',
        'suffix',
        'start_number',
        'number_of_digits',
        'is_default',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_number' => 'integer',
            'number_of_digits' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Format a numeric counter into this scheme's pattern.
     *
     * Example: prefix="INV-", suffix="", digits=4, counter=42 → "INV-0042"
     */
    public function format(int $counter): string
    {
        $padded = str_pad((string) $counter, $this->number_of_digits, '0', STR_PAD_LEFT);

        return ($this->prefix ?? '').$padded.($this->suffix ?? '');
    }
}
