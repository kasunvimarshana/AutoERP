<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Configuration\Domain\Entities\Country;
use Modules\Configuration\Domain\Entities\Currency;
use Modules\Configuration\Domain\Entities\Language;
use Modules\Configuration\Domain\Entities\Timezone;
use Modules\Configuration\Domain\RepositoryInterfaces\CountryRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\LanguageRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\TimezoneRepositoryInterface;
use Tests\TestCase;

class ConfigurationRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_repository_save_find_and_update_by_id(): void
    {
        /** @var CountryRepositoryInterface $repository */
        $repository = app(CountryRepositoryInterface::class);

        $saved = $repository->save(new Country(
            code: 'LK',
            name: 'Sri Lanka',
            phoneCode: '+94',
        ));

        $this->assertNotNull($saved->getId());
        $this->assertSame('LK', $saved->getCode());
        $this->assertSame('Sri Lanka', $saved->getName());
        $this->assertSame('+94', $saved->getPhoneCode());

        $found = $repository->findByCode('LK');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Sri Lanka', $found->getName());

        $updated = $repository->save(new Country(
            code: 'LK',
            name: 'Sri Lanka Updated',
            phoneCode: '+0094',
            id: $saved->getId(),
        ));

        $this->assertSame($saved->getId(), $updated->getId());
        $this->assertSame('Sri Lanka Updated', $updated->getName());
        $this->assertSame('+0094', $updated->getPhoneCode());

        $foundUpdated = $repository->findByCode('LK');

        $this->assertNotNull($foundUpdated);
        $this->assertSame('Sri Lanka Updated', $foundUpdated->getName());
        $this->assertSame('+0094', $foundUpdated->getPhoneCode());
    }

    public function test_currency_repository_save_find_and_update_by_id(): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);

        $saved = $repository->save(new Currency(
            code: 'USD',
            name: 'US Dollar',
            symbol: '$',
            decimalPlaces: 2,
            isActive: true,
        ));

        $this->assertNotNull($saved->getId());
        $this->assertSame('USD', $saved->getCode());
        $this->assertSame('US Dollar', $saved->getName());
        $this->assertSame('$', $saved->getSymbol());
        $this->assertSame(2, $saved->getDecimalPlaces());
        $this->assertTrue($saved->isActive());

        $found = $repository->findByCode('USD');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('US Dollar', $found->getName());

        $updated = $repository->save(new Currency(
            code: 'USD',
            name: 'United States Dollar',
            symbol: 'US$',
            decimalPlaces: 3,
            isActive: false,
            id: $saved->getId(),
        ));

        $this->assertSame($saved->getId(), $updated->getId());
        $this->assertSame('United States Dollar', $updated->getName());
        $this->assertSame('US$', $updated->getSymbol());
        $this->assertSame(3, $updated->getDecimalPlaces());
        $this->assertFalse($updated->isActive());

        $foundUpdated = $repository->findByCode('USD');

        $this->assertNotNull($foundUpdated);
        $this->assertSame('United States Dollar', $foundUpdated->getName());
        $this->assertSame('US$', $foundUpdated->getSymbol());
        $this->assertSame(3, $foundUpdated->getDecimalPlaces());
        $this->assertFalse($foundUpdated->isActive());
    }

    public function test_language_repository_save_find_and_update_by_id(): void
    {
        /** @var LanguageRepositoryInterface $repository */
        $repository = app(LanguageRepositoryInterface::class);

        $saved = $repository->save(new Language(
            code: 'en',
            name: 'English',
        ));

        $this->assertNotNull($saved->getId());
        $this->assertSame('en', $saved->getCode());
        $this->assertSame('English', $saved->getName());

        $found = $repository->findByCode('en');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('English', $found->getName());

        $updated = $repository->save(new Language(
            code: 'en',
            name: 'English (Global)',
            id: $saved->getId(),
        ));

        $this->assertSame($saved->getId(), $updated->getId());
        $this->assertSame('English (Global)', $updated->getName());

        $foundUpdated = $repository->findByCode('en');

        $this->assertNotNull($foundUpdated);
        $this->assertSame('English (Global)', $foundUpdated->getName());
    }

    public function test_timezone_repository_save_find_and_update_by_id(): void
    {
        /** @var TimezoneRepositoryInterface $repository */
        $repository = app(TimezoneRepositoryInterface::class);

        $saved = $repository->save(new Timezone(
            name: 'Asia/Colombo',
            offset: '+05:30',
        ));

        $this->assertNotNull($saved->getId());
        $this->assertSame('Asia/Colombo', $saved->getName());
        $this->assertSame('+05:30', $saved->getOffset());

        $found = $repository->findByName('Asia/Colombo');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('+05:30', $found->getOffset());

        $updated = $repository->save(new Timezone(
            name: 'Asia/Colombo',
            offset: '+0530',
            id: $saved->getId(),
        ));

        $this->assertSame($saved->getId(), $updated->getId());
        $this->assertSame('+0530', $updated->getOffset());

        $foundUpdated = $repository->findByName('Asia/Colombo');

        $this->assertNotNull($foundUpdated);
        $this->assertSame('+0530', $foundUpdated->getOffset());
    }
}
