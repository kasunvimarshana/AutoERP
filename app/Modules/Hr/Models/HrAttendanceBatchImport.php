<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Core\Models\User;
use Modules\Hr\Enums\BatchImportStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrAttendanceBatchImport extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_attendance_batch_imports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'imported_by_user_id' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
            'status' => BatchImportStatus::class,
            'errors_payload' => 'array',
        ]);
    }

    public function importedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }
}
