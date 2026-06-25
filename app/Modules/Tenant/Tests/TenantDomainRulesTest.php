<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use InvalidArgumentException;
use Modules\Tenant\Services\Rules\TenantDomainService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TenantDomainRulesTest extends TestCase
{
    private TenantDomainService $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = new TenantDomainService();
    }

    public function test_it_normalizes_tenant_codes_and_hostnames(): void
    {
        self::assertSame('ACME_01', $this->rules->normalizeCode(' acme_01 '));
        self::assertSame('erp.example.com', $this->rules->normalizeDomain(' ERP.Example.com. '));
    }

    #[DataProvider('invalidDomains')]
    public function test_it_rejects_values_that_are_not_plain_hostnames(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->rules->normalizeDomain($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidDomains(): iterable
    {
        yield 'scheme' => ['https://erp.example.com'];
        yield 'path' => ['erp.example.com/admin'];
        yield 'port' => ['erp.example.com:8443'];
        yield 'empty' => [''];
    }
}
