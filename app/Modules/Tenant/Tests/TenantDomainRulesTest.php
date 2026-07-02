<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use InvalidArgumentException;
use Modules\Tenant\Services\Rules\TenantValueNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TenantDomainRulesTest extends TestCase
{
    private TenantValueNormalizer $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = new TenantValueNormalizer(['platform.autoerp.com']);
    }

    public function test_it_normalizes_tenant_codes_and_public_hostnames(): void
    {
        self::assertSame('ACME_01', $this->rules->normalizeCode(' acme_01 '));
        self::assertSame('erp.example.org', $this->rules->normalizeDomain(' ERP.Example.org. '));
    }

    #[DataProvider('invalidDomains')]
    public function test_it_rejects_values_that_are_not_public_custom_hostnames(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->rules->normalizeDomain($value);
    }

    #[DataProvider('invalidDomainMessages')]
    public function test_it_explains_why_tenant_hostnames_are_rejected(string $value, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->rules->normalizeDomain($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidDomains(): iterable
    {
        yield 'scheme' => ['https://erp.example.org'];
        yield 'path' => ['erp.example.org/admin'];
        yield 'port' => ['erp.example.org:8443'];
        yield 'wildcard' => ['*.example.org'];
        yield 'reserved example tld' => ['erp.example'];
        yield 'localhost' => ['localhost'];
        yield 'ip address' => ['127.0.0.1'];
        yield 'configured central host' => ['platform.autoerp.com'];
        yield 'empty' => [''];
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidDomainMessages(): iterable
    {
        yield 'configured central host' => [
            'platform.autoerp.com',
            'The platform host cannot also be used as a tenant domain.',
        ];

        yield 'ip address' => [
            '127.0.0.1',
            'IP addresses cannot become tenant domains.',
        ];

        yield 'reserved local hostname' => [
            'localhost',
            'Reserved local, test, internal, and example domains cannot become tenant domains.',
        ];

        yield 'single label hostname' => [
            'autoerp',
            'Use a fully qualified public tenant hostname such as erp.example.com.',
        ];
    }
}
