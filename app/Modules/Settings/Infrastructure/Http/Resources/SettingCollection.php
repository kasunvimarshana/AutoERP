<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SettingCollection extends ResourceCollection
{
    public $collects = SettingResource::class;
}
