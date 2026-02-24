<?php

namespace Modules\Localisation\Domain\Events;

class LanguagePackCreated
{
    public function __construct(
        public readonly string $languagePackId,
        public readonly string $tenantId,
        public readonly string $locale,
    ) {}
}
