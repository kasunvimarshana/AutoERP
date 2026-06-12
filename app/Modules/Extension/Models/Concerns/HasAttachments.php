<?php

declare(strict_types=1);

namespace Modules\Extension\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Extension\Models\AttachmentModel;

trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(AttachmentModel::class, 'attachable');
    }
}
