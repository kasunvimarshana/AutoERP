<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Modules\Core\Services\CodeGeneratorService;
use Tests\TestCase;

class CodeGeneratorServiceTest extends TestCase
{
    private CodeGeneratorService $codeGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->codeGenerator = new CodeGeneratorService;
    }

    public function test_can_generate_code_with_prefix(): void
    {
        $code = $this->codeGenerator->generate('TEST-');

        $this->assertStringStartsWith('TEST-', $code);
        $this->assertEquals(13, strlen($code)); // TEST- (5) + 8 chars
    }

    public function test_can_generate_code_without_uniqueness_check(): void
    {
        $code1 = $this->codeGenerator->generate('CUST-');
        $code2 = $this->codeGenerator->generate('CUST-');

        // Without uniqueness check, codes should be different
        $this->assertNotEquals($code1, $code2);
    }

    public function test_can_generate_unique_code_with_validation(): void
    {
        $existingCodes = ['TEST-ABCD1234', 'TEST-EFGH5678'];

        $code = $this->codeGenerator->generate(
            'TEST-',
            function (string $code) use ($existingCodes) {
                return in_array($code, $existingCodes);
            }
        );

        $this->assertStringStartsWith('TEST-', $code);
        $this->assertNotContains($code, $existingCodes);
    }

    public function test_throws_exception_when_cannot_generate_unique_code(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to generate unique code');

        // Always return true (all codes exist)
        $this->codeGenerator->generate(
            'TEST-',
            fn () => true,
            8,
            3 // Max 3 attempts
        );
    }

    public function test_can_generate_code_with_custom_length(): void
    {
        $code = $this->codeGenerator->generate('ORD-', null, 12);

        $this->assertStringStartsWith('ORD-', $code);
        $this->assertEquals(16, strlen($code)); // ORD- (4) + 12 chars
    }

    public function test_can_generate_sequential_code(): void
    {
        $code1 = $this->codeGenerator->generateSequential('INV-', 1);
        $code2 = $this->codeGenerator->generateSequential('INV-', 42);
        $code3 = $this->codeGenerator->generateSequential('INV-', 999999);

        $this->assertEquals('INV-000001', $code1);
        $this->assertEquals('INV-000042', $code2);
        $this->assertEquals('INV-999999', $code3);
    }

    public function test_can_generate_sequential_code_with_custom_padding(): void
    {
        $code = $this->codeGenerator->generateSequential('PO-', 5, 3);

        $this->assertEquals('PO-005', $code);
    }

    public function test_can_generate_date_based_code(): void
    {
        $code = $this->codeGenerator->generateDateBased('QUO-', 'Ymd', null, 6);

        $expectedPrefix = 'QUO-'.date('Ymd').'-';
        $this->assertStringStartsWith($expectedPrefix, $code);
        $this->assertEquals(strlen('QUO-') + strlen(date('Ymd')) + 1 + 6, strlen($code));
    }

    public function test_can_generate_date_based_code_with_uniqueness_check(): void
    {
        $existingCodes = ['QUO-'.date('Ymd').'-ABC123'];

        $code = $this->codeGenerator->generateDateBased(
            'QUO-',
            'Ymd',
            null,
            6,
            fn (string $code) => in_array($code, $existingCodes)
        );

        $this->assertStringStartsWith('QUO-'.date('Ymd'), $code);
        $this->assertNotContains($code, $existingCodes);
    }

    public function test_can_generate_date_based_code_with_sequence(): void
    {
        $code = $this->codeGenerator->generateDateBased('QUO-', 'Ymd', 123);

        $expected = 'QUO-'.date('Ymd').'-0123';
        $this->assertEquals($expected, $code);
    }

    public function test_can_validate_code_format(): void
    {
        $validCode = 'CUST-ABCD1234';
        $invalidPrefix = 'VEND-ABCD1234';
        $invalidLength = 'CUST-ABC';

        $this->assertTrue($this->codeGenerator->validateFormat($validCode, 'CUST-'));
        $this->assertTrue($this->codeGenerator->validateFormat($validCode, 'CUST-', 13));
        $this->assertFalse($this->codeGenerator->validateFormat($invalidPrefix, 'CUST-'));
        $this->assertFalse($this->codeGenerator->validateFormat($invalidLength, 'CUST-', 13));
    }

    public function test_can_extract_unique_part(): void
    {
        $code = 'CUST-ABCD1234';
        $uniquePart = $this->codeGenerator->extractUniquePart($code, 'CUST-');

        $this->assertEquals('ABCD1234', $uniquePart);
    }

    public function test_extract_unique_part_returns_code_if_no_prefix_match(): void
    {
        $code = 'VEND-ABCD1234';
        $uniquePart = $this->codeGenerator->extractUniquePart($code, 'CUST-');

        $this->assertEquals('VEND-ABCD1234', $uniquePart);
    }

    public function test_generates_different_codes_on_subsequent_calls(): void
    {
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = $this->codeGenerator->generate('TEST-');
        }

        // All codes should be unique
        $uniqueCodes = array_unique($codes);
        $this->assertCount(100, $uniqueCodes);
    }
}
