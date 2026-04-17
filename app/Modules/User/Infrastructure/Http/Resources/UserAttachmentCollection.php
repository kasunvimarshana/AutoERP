<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserAttachmentCollection extends ResourceCollection
{
    public string $collects = UserAttachmentResource::class;
}
