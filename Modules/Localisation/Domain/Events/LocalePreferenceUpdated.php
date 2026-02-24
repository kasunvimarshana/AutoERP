<?php

namespace Modules\Localisation\Domain\Events;

class LocalePreferenceUpdated
{
    public function __construct(
        public readonly string $userId,
        public readonly string $tenantId,
        public readonly string $locale,
        public readonly string $timezone,
    ) {}
}
