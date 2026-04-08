<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Events;

use Modules\Configuration\Domain\Entities\Setting;

class SettingUpdated
{
    public function __construct(
        public readonly Setting $setting,
    ) {}
}
