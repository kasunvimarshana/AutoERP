<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Enums;

use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\Country;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\Currency;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\Language;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\Timezone;

enum ConfigurationRecordType: string
{
    case Country = 'country';
    case Currency = 'currency';
    case Language = 'language';
    case Timezone = 'timezone';

    public function tableName(): string
    {
        return match ($this) {
            self::Country => 'countries',
            self::Currency => 'currencies',
            self::Language => 'languages',
            self::Timezone => 'timezones',
        };
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::Country => Country::class,
            self::Currency => Currency::class,
            self::Language => Language::class,
            self::Timezone => Timezone::class,
        };
    }

    public function uniqueField(): string
    {
        return match ($this) {
            self::Timezone => 'name',
            default => 'code',
        };
    }
}
