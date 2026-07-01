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
        $this->rules = new TenantValueNormalizer(['platform.autoerp.example']);
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
        yield 'configured central host' => ['platform.autoerp.example'];
        yield 'empty' => [''];
    }
}
