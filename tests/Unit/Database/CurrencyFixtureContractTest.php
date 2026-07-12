<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\CurrencyFixture;
use Tests\TestCase;

final class CurrencyFixtureContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_currency_codes_are_unique_and_match_the_schema_contract(): void
    {
        $firstId = CurrencyFixture::create();
        $secondId = CurrencyFixture::create();

        $firstCode = (string) \DB::table('currencies')->where('id', $firstId)->value('code');
        $secondCode = (string) \DB::table('currencies')->where('id', $secondId)->value('code');

        self::assertMatchesRegularExpression('/^[A-Z]{3}$/D', $firstCode);
        self::assertMatchesRegularExpression('/^[A-Z]{3}$/D', $secondCode);
        self::assertNotSame($firstCode, $secondCode);
    }

    public function test_invalid_explicit_currency_code_is_rejected_before_database_insert(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Currency test fixture requires a three-letter code.');

        CurrencyFixture::create(['code' => 'LKR-TEST']);
    }
}
