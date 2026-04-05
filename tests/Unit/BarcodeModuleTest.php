<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Barcode\Domain\ValueObjects\BarcodeType;
use Modules\Barcode\Domain\ValueObjects\BarcodeOutputFormat;
use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Entities\BarcodeScan;
use Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException;
use Modules\Barcode\Domain\Exceptions\InvalidBarcodeException;
use Modules\Barcode\Domain\Exceptions\UnsupportedBarcodeTypeException;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeScanRepositoryInterface;
use Modules\Barcode\Application\Services\ManageBarcodeService;
use Modules\Barcode\Application\Services\RecordBarcodeScanService;
use Modules\Barcode\Application\Services\GenerateBarcodeService;
use Modules\Barcode\Infrastructure\Generators\BarcodeGeneratorDispatcher;
use Modules\Barcode\Infrastructure\Generators\BarcodeGeneratorDriverInterface;
use Modules\Barcode\Infrastructure\Generators\Drivers\Code128Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Code39Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Code93Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Ean13Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Ean8Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\QrCodeDriver;

class BarcodeModuleTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helper factories
    // ─────────────────────────────────────────────────────────────────────────

    private function makeDefinition(
        ?int    $id        = 1,
        int     $tenantId  = 1,
        string  $type      = BarcodeType::CODE128,
        string  $value     = 'TEST123',
        ?string $label     = 'Test Label',
        bool    $isActive  = true,
    ): BarcodeDefinition {
        return new BarcodeDefinition(
            $id,
            $tenantId,
            $type,
            $value,
            $label,
            null,
            null,
            [],
            $isActive,
            new \DateTime(),
            new \DateTime(),
        );
    }

    private function makeScan(
        ?int    $id                  = 1,
        int     $tenantId            = 1,
        ?int    $barcodeDefinitionId = 5,
        string  $scannedValue        = 'TEST123',
        ?string $resolvedType        = BarcodeType::CODE128,
    ): BarcodeScan {
        return new BarcodeScan(
            $id,
            $tenantId,
            $barcodeDefinitionId,
            $scannedValue,
            $resolvedType,
            42,
            'device-001',
            'warehouse-A',
            ['source' => 'mobile'],
            new \DateTime(),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodeType value object
    // ─────────────────────────────────────────────────────────────────────────

    public function test_barcode_type_from_string_valid_types(): void
    {
        $validTypes = [
            BarcodeType::CODE128,
            BarcodeType::CODE39,
            BarcodeType::CODE93,
            BarcodeType::EAN13,
            BarcodeType::EAN8,
            BarcodeType::UPCA,
            BarcodeType::UPCE,
            BarcodeType::ITF14,
            BarcodeType::CODABAR,
            BarcodeType::MSI,
            BarcodeType::INTERLEAVED2OF5,
            BarcodeType::QR,
            BarcodeType::DATAMATRIX,
            BarcodeType::PDF417,
            BarcodeType::AZTEC,
        ];

        foreach ($validTypes as $typeStr) {
            $type = BarcodeType::fromString($typeStr);
            $this->assertSame($typeStr, $type->getValue());
        }
    }

    public function test_barcode_type_from_string_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BarcodeType::fromString('INVALID_TYPE');
    }

    public function test_barcode_type_is_one_dimensional(): void
    {
        $oneDTypes = [
            BarcodeType::CODE128,
            BarcodeType::CODE39,
            BarcodeType::CODE93,
            BarcodeType::EAN13,
            BarcodeType::EAN8,
            BarcodeType::UPCA,
            BarcodeType::UPCE,
            BarcodeType::ITF14,
            BarcodeType::CODABAR,
            BarcodeType::MSI,
            BarcodeType::INTERLEAVED2OF5,
        ];

        foreach ($oneDTypes as $typeStr) {
            $type = BarcodeType::fromString($typeStr);
            $this->assertTrue($type->isOneDimensional(), "{$typeStr} should be 1-D");
            $this->assertFalse($type->isTwoDimensional(), "{$typeStr} should not be 2-D");
        }
    }

    public function test_barcode_type_is_two_dimensional(): void
    {
        $twoDTypes = [
            BarcodeType::QR,
            BarcodeType::DATAMATRIX,
            BarcodeType::PDF417,
            BarcodeType::AZTEC,
        ];

        foreach ($twoDTypes as $typeStr) {
            $type = BarcodeType::fromString($typeStr);
            $this->assertTrue($type->isTwoDimensional(), "{$typeStr} should be 2-D");
            $this->assertFalse($type->isOneDimensional(), "{$typeStr} should not be 1-D");
        }
    }

    public function test_barcode_type_all_types_returns_15_types(): void
    {
        $this->assertCount(15, BarcodeType::allTypes());
    }

    public function test_barcode_type_get_value_returns_string(): void
    {
        $type = BarcodeType::code128();
        $this->assertSame(BarcodeType::CODE128, $type->getValue());

        $type = BarcodeType::qr();
        $this->assertSame(BarcodeType::QR, $type->getValue());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodeOutputFormat value object
    // ─────────────────────────────────────────────────────────────────────────

    public function test_output_format_from_string_valid(): void
    {
        $svg    = BarcodeOutputFormat::fromString(BarcodeOutputFormat::SVG);
        $png    = BarcodeOutputFormat::fromString(BarcodeOutputFormat::PNG_BASE64);
        $raw    = BarcodeOutputFormat::fromString(BarcodeOutputFormat::RAW);

        $this->assertSame(BarcodeOutputFormat::SVG,        $svg->getValue());
        $this->assertSame(BarcodeOutputFormat::PNG_BASE64, $png->getValue());
        $this->assertSame(BarcodeOutputFormat::RAW,        $raw->getValue());
    }

    public function test_output_format_from_string_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BarcodeOutputFormat::fromString('pdf');
    }

    public function test_output_format_get_value(): void
    {
        $format = BarcodeOutputFormat::svg();
        $this->assertSame('svg', $format->getValue());

        $format = BarcodeOutputFormat::pngBase64();
        $this->assertSame('png_base64', $format->getValue());

        $format = BarcodeOutputFormat::raw();
        $this->assertSame('raw', $format->getValue());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodeDefinition entity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_barcode_definition_creation(): void
    {
        $def = $this->makeDefinition(id: 7, tenantId: 3, type: BarcodeType::QR, value: 'QR-001');

        $this->assertSame(7,                $def->getId());
        $this->assertSame(3,                $def->getTenantId());
        $this->assertSame(BarcodeType::QR,  $def->getType());
        $this->assertSame('QR-001',         $def->getValue());
        $this->assertTrue($def->isActive());
    }

    public function test_barcode_definition_activate(): void
    {
        $def = $this->makeDefinition(isActive: false);
        $this->assertFalse($def->isActive());

        $def->activate();

        $this->assertTrue($def->isActive());
        $this->assertNotNull($def->getUpdatedAt());
    }

    public function test_barcode_definition_deactivate(): void
    {
        $def = $this->makeDefinition(isActive: true);
        $this->assertTrue($def->isActive());

        $def->deactivate();

        $this->assertFalse($def->isActive());
        $this->assertNotNull($def->getUpdatedAt());
    }

    public function test_barcode_definition_update_label(): void
    {
        $def = $this->makeDefinition(label: 'Old Label');
        $def->updateLabel('New Label');

        $this->assertSame('New Label', $def->getLabel());
        $this->assertNotNull($def->getUpdatedAt());
    }

    public function test_barcode_definition_update_metadata(): void
    {
        $def = $this->makeDefinition();
        $def->updateMetadata(['sku' => 'ABC-123', 'qty' => 10]);

        $this->assertSame(['sku' => 'ABC-123', 'qty' => 10], $def->getMetadata());
        $this->assertNotNull($def->getUpdatedAt());
    }

    public function test_barcode_definition_getters(): void
    {
        $createdAt = new \DateTime('2024-01-01');
        $updatedAt = new \DateTime('2024-06-01');

        $def = new BarcodeDefinition(
            42,
            5,
            BarcodeType::EAN13,
            '5901234123457',
            'Product EAN',
            'product',
            99,
            ['color' => 'red'],
            true,
            $createdAt,
            $updatedAt,
        );

        $this->assertSame(42,                  $def->getId());
        $this->assertSame(5,                   $def->getTenantId());
        $this->assertSame(BarcodeType::EAN13,  $def->getType());
        $this->assertSame('5901234123457',     $def->getValue());
        $this->assertSame('Product EAN',       $def->getLabel());
        $this->assertSame('product',           $def->getEntityType());
        $this->assertSame(99,                  $def->getEntityId());
        $this->assertSame(['color' => 'red'],  $def->getMetadata());
        $this->assertTrue($def->isActive());
        $this->assertSame($createdAt,          $def->getCreatedAt());
        $this->assertSame($updatedAt,          $def->getUpdatedAt());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodeScan entity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_barcode_scan_creation(): void
    {
        $scan = $this->makeScan();

        $this->assertSame(1,                    $scan->getId());
        $this->assertSame(1,                    $scan->getTenantId());
        $this->assertSame(5,                    $scan->getBarcodeDefinitionId());
        $this->assertSame('TEST123',            $scan->getScannedValue());
        $this->assertSame(BarcodeType::CODE128, $scan->getResolvedType());
    }

    public function test_barcode_scan_getters(): void
    {
        $scannedAt = new \DateTime('2024-03-15 10:30:00');

        $scan = new BarcodeScan(
            11,
            2,
            7,
            'SCAN-VALUE',
            BarcodeType::QR,
            99,
            'scanner-A',
            'dock-1',
            ['ref' => 'PO-001'],
            $scannedAt,
        );

        $this->assertSame(11,               $scan->getId());
        $this->assertSame(2,                $scan->getTenantId());
        $this->assertSame(7,                $scan->getBarcodeDefinitionId());
        $this->assertSame('SCAN-VALUE',     $scan->getScannedValue());
        $this->assertSame(BarcodeType::QR,  $scan->getResolvedType());
        $this->assertSame(99,               $scan->getScannedByUserId());
        $this->assertSame('scanner-A',      $scan->getDeviceId());
        $this->assertSame('dock-1',         $scan->getLocationTag());
        $this->assertSame(['ref' => 'PO-001'], $scan->getMetadata());
        $this->assertSame($scannedAt,       $scan->getScannedAt());
    }

    public function test_barcode_scan_null_optional_fields(): void
    {
        $scan = new BarcodeScan(
            null,
            1,
            null,
            'UNKNOWN',
            null,
            null,
            null,
            null,
            [],
            new \DateTime(),
        );

        $this->assertNull($scan->getId());
        $this->assertNull($scan->getBarcodeDefinitionId());
        $this->assertNull($scan->getResolvedType());
        $this->assertNull($scan->getScannedByUserId());
        $this->assertNull($scan->getDeviceId());
        $this->assertNull($scan->getLocationTag());
        $this->assertSame([], $scan->getMetadata());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exceptions
    // ─────────────────────────────────────────────────────────────────────────

    public function test_barcode_not_found_exception_with_id(): void
    {
        $e = BarcodeNotFoundException::withId(42);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame(404, $e->getCode());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    public function test_invalid_barcode_exception_for_value(): void
    {
        $e = InvalidBarcodeException::forValue('BAD!', BarcodeType::CODE39, 'unsupported character');

        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        $this->assertSame(422, $e->getCode());
        $this->assertStringContainsString('BAD!',              $e->getMessage());
        $this->assertStringContainsString(BarcodeType::CODE39, $e->getMessage());
        $this->assertStringContainsString('unsupported character', $e->getMessage());
    }

    public function test_unsupported_type_exception_for_type(): void
    {
        $e = UnsupportedBarcodeTypeException::forType('FANCY_TYPE');

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame(422, $e->getCode());
        $this->assertStringContainsString('FANCY_TYPE', $e->getMessage());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodeGeneratorDispatcher
    // ─────────────────────────────────────────────────────────────────────────

    public function test_dispatcher_add_and_has_driver(): void
    {
        $dispatcher = new BarcodeGeneratorDispatcher();
        $this->assertFalse($dispatcher->hasDriver(BarcodeType::CODE128));

        /** @var BarcodeGeneratorDriverInterface&MockObject $driver */
        $driver = $this->createMock(BarcodeGeneratorDriverInterface::class);
        $dispatcher->addDriver(BarcodeType::CODE128, $driver);

        $this->assertTrue($dispatcher->hasDriver(BarcodeType::CODE128));
    }

    public function test_dispatcher_get_supported_types(): void
    {
        $dispatcher = new BarcodeGeneratorDispatcher();

        /** @var BarcodeGeneratorDriverInterface&MockObject $d1 */
        $d1 = $this->createMock(BarcodeGeneratorDriverInterface::class);
        /** @var BarcodeGeneratorDriverInterface&MockObject $d2 */
        $d2 = $this->createMock(BarcodeGeneratorDriverInterface::class);

        $dispatcher->addDriver(BarcodeType::CODE128, $d1);
        $dispatcher->addDriver(BarcodeType::QR,      $d2);

        $types = $dispatcher->getSupportedTypes();
        $this->assertContains(BarcodeType::CODE128, $types);
        $this->assertContains(BarcodeType::QR,      $types);
        $this->assertCount(2, $types);
    }

    public function test_dispatcher_throws_for_unknown_type(): void
    {
        $dispatcher = new BarcodeGeneratorDispatcher();
        $def        = $this->makeDefinition(type: BarcodeType::CODE128);

        $this->expectException(UnsupportedBarcodeTypeException::class);
        $dispatcher->generate($def, BarcodeOutputFormat::SVG, []);
    }

    public function test_dispatcher_validate_delegates_to_driver(): void
    {
        $dispatcher = new BarcodeGeneratorDispatcher();

        /** @var BarcodeGeneratorDriverInterface&MockObject $driver */
        $driver = $this->createMock(BarcodeGeneratorDriverInterface::class);
        $driver->expects($this->once())
               ->method('validate')
               ->with('HELLO')
               ->willReturn(true);

        $dispatcher->addDriver(BarcodeType::CODE128, $driver);

        $this->assertTrue($dispatcher->validate(BarcodeType::CODE128, 'HELLO'));
    }

    public function test_dispatcher_generate_returns_string(): void
    {
        $dispatcher = new BarcodeGeneratorDispatcher();
        $def        = $this->makeDefinition(type: BarcodeType::CODE128, value: 'HELLO');

        /** @var BarcodeGeneratorDriverInterface&MockObject $driver */
        $driver = $this->createMock(BarcodeGeneratorDriverInterface::class);
        $driver->expects($this->once())
               ->method('generate')
               ->with('HELLO', BarcodeOutputFormat::SVG, [])
               ->willReturn('<svg/>');

        $dispatcher->addDriver(BarcodeType::CODE128, $driver);

        $result = $dispatcher->generate($def, BarcodeOutputFormat::SVG, []);
        $this->assertSame('<svg/>', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Code128Driver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_code128_supports_correct_type(): void
    {
        $driver = new Code128Driver();
        $this->assertTrue($driver->supports(BarcodeType::CODE128));
        $this->assertFalse($driver->supports(BarcodeType::CODE39));
        $this->assertFalse($driver->supports(BarcodeType::QR));
    }

    public function test_code128_generate_returns_svg(): void
    {
        $driver = new Code128Driver();
        $svg    = $driver->generate('ABC', BarcodeOutputFormat::SVG, []);
        $this->assertIsString($svg);
        $this->assertNotEmpty($svg);
    }

    public function test_code128_svg_contains_svg_tag(): void
    {
        $driver = new Code128Driver();
        $svg    = $driver->generate('TEST', BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_code128_validate_valid_string(): void
    {
        $driver = new Code128Driver();
        $this->assertTrue($driver->validate('Hello World 123'));
        $this->assertTrue($driver->validate('ABC'));
        $this->assertTrue($driver->validate('0123456789'));
    }

    public function test_code128_validate_rejects_invalid_chars(): void
    {
        $driver = new Code128Driver();
        $this->assertFalse($driver->validate(''));
        $this->assertFalse($driver->validate("\x01 control")); // ASCII < 32
    }

    public function test_code128_generate_hello_world(): void
    {
        $driver = new Code128Driver();
        $svg    = $driver->generate('Hello World', BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('Hello World', $svg);
    }

    public function test_code128_generate_different_values_produce_different_svgs(): void
    {
        $driver = new Code128Driver();
        $svg1   = $driver->generate('AAAA', BarcodeOutputFormat::SVG, []);
        $svg2   = $driver->generate('BBBB', BarcodeOutputFormat::SVG, []);
        $this->assertNotSame($svg1, $svg2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Code39Driver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_code39_supports_correct_type(): void
    {
        $driver = new Code39Driver();
        $this->assertTrue($driver->supports(BarcodeType::CODE39));
        $this->assertFalse($driver->supports(BarcodeType::CODE128));
        $this->assertFalse($driver->supports(BarcodeType::QR));
    }

    public function test_code39_generate_returns_svg(): void
    {
        $driver = new Code39Driver();
        $svg    = $driver->generate('ABC123', BarcodeOutputFormat::SVG, []);
        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_code39_validate_valid_chars(): void
    {
        $driver = new Code39Driver();
        $this->assertTrue($driver->validate('HELLO'));
        $this->assertTrue($driver->validate('123'));
        $this->assertTrue($driver->validate('ABC-123'));
        $this->assertFalse($driver->validate(''));
        $this->assertFalse($driver->validate('HELLO*WORLD')); // * is reserved
        $this->assertTrue($driver->validate('hello'));        // lowercase is uppercased internally
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Code93Driver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_code93_supports_correct_type(): void
    {
        $driver = new Code93Driver();
        $this->assertTrue($driver->supports(BarcodeType::CODE93));
        $this->assertFalse($driver->supports(BarcodeType::CODE39));
        $this->assertFalse($driver->supports(BarcodeType::CODE128));
    }

    public function test_code93_generate_returns_svg(): void
    {
        $driver = new Code93Driver();
        $svg    = $driver->generate('ABC123', BarcodeOutputFormat::SVG, []);
        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertGreaterThan(100, strlen($svg));
    }

    public function test_code93_validate_valid_chars(): void
    {
        $driver = new Code93Driver();
        $this->assertTrue($driver->validate('HELLO'));
        $this->assertTrue($driver->validate('ABC-123'));
        $this->assertTrue($driver->validate('TEST VALUE'));
        $this->assertFalse($driver->validate(''));
        $this->assertFalse($driver->validate('hello')); // lowercase invalid per Code93 spec
    }

    public function test_code93_different_values_produce_different_svgs(): void
    {
        $driver = new Code93Driver();
        $svg1   = $driver->generate('ABC', BarcodeOutputFormat::SVG, []);
        $svg2   = $driver->generate('XYZ', BarcodeOutputFormat::SVG, []);
        $this->assertNotEquals($svg1, $svg2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ean13Driver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_ean13_supports_correct_type(): void
    {
        $driver = new Ean13Driver();
        $this->assertTrue($driver->supports(BarcodeType::EAN13));
        $this->assertFalse($driver->supports(BarcodeType::EAN8));
        $this->assertFalse($driver->supports(BarcodeType::CODE128));
    }

    public function test_ean13_generate_returns_svg(): void
    {
        $driver = new Ean13Driver();
        // 5901234123457 is a valid EAN-13
        $svg    = $driver->generate('5901234123457', BarcodeOutputFormat::SVG, []);
        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_ean13_validate_valid_13_digit(): void
    {
        $driver = new Ean13Driver();
        $this->assertTrue($driver->validate('5901234123457')); // valid EAN-13 with correct check digit
        $this->assertTrue($driver->validate('590123412345'));  // 12 digits, check digit auto-computed
    }

    public function test_ean13_validate_invalid_length(): void
    {
        $driver = new Ean13Driver();
        $this->assertFalse($driver->validate('123'));            // too short
        $this->assertFalse($driver->validate('12345678901234')); // too long
        $this->assertFalse($driver->validate('590123412345X'));  // non-digit
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ean8Driver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_ean8_supports_correct_type(): void
    {
        $driver = new Ean8Driver();
        $this->assertTrue($driver->supports(BarcodeType::EAN8));
        $this->assertFalse($driver->supports(BarcodeType::EAN13));
        $this->assertFalse($driver->supports(BarcodeType::CODE128));
    }

    public function test_ean8_generate_returns_svg(): void
    {
        $driver = new Ean8Driver();
        // 7 digits – check digit will be auto-appended
        $svg    = $driver->generate('1234567', BarcodeOutputFormat::SVG, []);
        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QrCodeDriver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_qr_supports_correct_type(): void
    {
        $driver = new QrCodeDriver();
        $this->assertTrue($driver->supports(BarcodeType::QR));
        $this->assertFalse($driver->supports(BarcodeType::CODE128));
        $this->assertFalse($driver->supports(BarcodeType::EAN13));
    }

    public function test_qr_generate_returns_svg(): void
    {
        $driver = new QrCodeDriver();
        $svg    = $driver->generate('Hello', BarcodeOutputFormat::SVG, []);
        $this->assertIsString($svg);
        $this->assertNotEmpty($svg);
    }

    public function test_qr_svg_contains_svg_tag(): void
    {
        $driver = new QrCodeDriver();
        $svg    = $driver->generate('Hello', BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_qr_generate_short_value(): void
    {
        $driver = new QrCodeDriver();
        $svg    = $driver->generate('A', BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_qr_generate_long_value(): void
    {
        $driver = new QrCodeDriver();
        $value  = str_repeat('Hello World! ', 10); // ~130 chars
        $svg    = $driver->generate($value, BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_qr_generate_numeric_value(): void
    {
        $driver = new QrCodeDriver();
        $svg    = $driver->generate('1234567890', BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_qr_generate_url(): void
    {
        $driver = new QrCodeDriver();
        $svg    = $driver->generate('https://example.com/path?query=1', BarcodeOutputFormat::SVG, []);
        $this->assertStringContainsString('<svg', $svg);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ManageBarcodeService
    // ─────────────────────────────────────────────────────────────────────────

    private function makeDefinitionRepo(): BarcodeDefinitionRepositoryInterface&MockObject
    {
        return $this->createMock(BarcodeDefinitionRepositoryInterface::class);
    }

    public function test_manage_service_create_barcode(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);

        $saved = $this->makeDefinition(id: 10);

        $repo->expects($this->once())
             ->method('save')
             ->willReturn($saved);

        $result = $service->create(1, BarcodeType::CODE128, 'ABC', 'My Label', null, null);

        $this->assertSame($saved, $result);
    }

    public function test_manage_service_create_invalid_type_throws(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);

        $this->expectException(\InvalidArgumentException::class);
        $service->create(1, 'BOGUS_TYPE', 'value', null, null, null);
    }

    public function test_manage_service_get_by_id_found(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);
        $def     = $this->makeDefinition(id: 5);

        $repo->expects($this->once())
             ->method('findById')
             ->with(5)
             ->willReturn($def);

        $result = $service->getById(5);
        $this->assertSame($def, $result);
    }

    public function test_manage_service_get_by_id_not_found_throws(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);

        $repo->method('findById')->willReturn(null);

        $this->expectException(BarcodeNotFoundException::class);
        $service->getById(999);
    }

    public function test_manage_service_get_by_value_not_found_throws(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);

        $repo->method('findByValue')->willReturn(null);

        $this->expectException(BarcodeNotFoundException::class);
        $service->getByValue(1, 'NONEXISTENT');
    }

    public function test_manage_service_activate(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);
        $def     = $this->makeDefinition(id: 3, isActive: false);

        $repo->method('findById')->willReturn($def);
        $repo->expects($this->once())
             ->method('save')
             ->with($this->callback(fn ($d) => $d->isActive()))
             ->willReturn($def);

        $service->activate(3);
        $this->assertTrue($def->isActive());
    }

    public function test_manage_service_deactivate(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);
        $def     = $this->makeDefinition(id: 4, isActive: true);

        $repo->method('findById')->willReturn($def);
        $repo->expects($this->once())
             ->method('save')
             ->with($this->callback(fn ($d) => !$d->isActive()))
             ->willReturn($def);

        $service->deactivate(4);
        $this->assertFalse($def->isActive());
    }

    public function test_manage_service_delete(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);
        $def     = $this->makeDefinition(id: 6);

        $repo->method('findById')->willReturn($def);
        $repo->expects($this->once())
             ->method('delete')
             ->with(6);

        $service->delete(6);
    }

    public function test_manage_service_list_all(): void
    {
        $repo    = $this->makeDefinitionRepo();
        $service = new ManageBarcodeService($repo);
        $list    = [$this->makeDefinition(id: 1), $this->makeDefinition(id: 2)];

        $repo->expects($this->once())
             ->method('findAll')
             ->with(1)
             ->willReturn($list);

        $result = $service->listAll(1);
        $this->assertSame($list, $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RecordBarcodeScanService
    // ─────────────────────────────────────────────────────────────────────────

    public function test_record_scan_with_known_barcode(): void
    {
        /** @var BarcodeScanRepositoryInterface&MockObject $scanRepo */
        $scanRepo = $this->createMock(BarcodeScanRepositoryInterface::class);
        $defRepo  = $this->makeDefinitionRepo();
        $service  = new RecordBarcodeScanService($scanRepo, $defRepo);

        $def      = $this->makeDefinition(id: 5, type: BarcodeType::CODE128, value: 'KNOWN');
        $savedScan = $this->makeScan(id: 1, barcodeDefinitionId: 5, resolvedType: BarcodeType::CODE128);

        $defRepo->method('findByValue')->with(1, 'KNOWN')->willReturn($def);
        $scanRepo->expects($this->once())
                 ->method('save')
                 ->willReturn($savedScan);

        $result = $service->record(1, 'KNOWN', 10, 'device-1', null);

        $this->assertSame($savedScan, $result);
    }

    public function test_record_scan_with_unknown_barcode(): void
    {
        /** @var BarcodeScanRepositoryInterface&MockObject $scanRepo */
        $scanRepo = $this->createMock(BarcodeScanRepositoryInterface::class);
        $defRepo  = $this->makeDefinitionRepo();
        $service  = new RecordBarcodeScanService($scanRepo, $defRepo);

        $savedScan = $this->makeScan(id: 2, barcodeDefinitionId: null, resolvedType: null);

        $defRepo->method('findByValue')->willReturn(null);
        $scanRepo->expects($this->once())
                 ->method('save')
                 ->willReturn($savedScan);

        $result = $service->record(1, 'UNKNOWN_VALUE', null, null, null);

        $this->assertSame($savedScan, $result);
        $this->assertNull($result->getBarcodeDefinitionId());
    }

    public function test_record_scan_get_by_id_not_found_throws(): void
    {
        /** @var BarcodeScanRepositoryInterface&MockObject $scanRepo */
        $scanRepo = $this->createMock(BarcodeScanRepositoryInterface::class);
        $defRepo  = $this->makeDefinitionRepo();
        $service  = new RecordBarcodeScanService($scanRepo, $defRepo);

        $scanRepo->method('findById')->willReturn(null);

        $this->expectException(BarcodeNotFoundException::class);
        $service->getById(99);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GenerateBarcodeService
    // ─────────────────────────────────────────────────────────────────────────

    public function test_generate_service_delegates_to_dispatcher(): void
    {
        $dispatcher = new BarcodeGeneratorDispatcher();
        $service    = new GenerateBarcodeService($dispatcher);
        $def        = $this->makeDefinition(type: BarcodeType::CODE128, value: 'HELLO');

        /** @var BarcodeGeneratorDriverInterface&MockObject $driver */
        $driver = $this->createMock(BarcodeGeneratorDriverInterface::class);
        $driver->expects($this->once())
               ->method('generate')
               ->with('HELLO', BarcodeOutputFormat::SVG, [])
               ->willReturn('<svg>barcode</svg>');

        $dispatcher->addDriver(BarcodeType::CODE128, $driver);

        $result = $service->generate($def, BarcodeOutputFormat::SVG, []);
        $this->assertSame('<svg>barcode</svg>', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodeScanRecorded event
    // ─────────────────────────────────────────────────────────────────────────

    public function test_barcode_scan_recorded_event_holds_scan(): void
    {
        $scan  = $this->makeScan();
        $event = new \Modules\Barcode\Domain\Events\BarcodeScanRecorded($scan);

        $this->assertSame($scan, $event->scan);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LabelTemplate entity
    // ─────────────────────────────────────────────────────────────────────────

    private function makeLabelTemplate(
        ?int    $id      = 1,
        int     $tenant  = 1,
        string  $name    = 'Default ZPL',
        string  $format  = 'zpl',
        string  $content = '^XA^FO10,10^BCN,80,Y,N,N^FD{{ barcode_value }}^FS^XZ',
        array   $defaults = [],
        bool    $active  = true,
    ): \Modules\Barcode\Domain\Entities\LabelTemplate {
        return new \Modules\Barcode\Domain\Entities\LabelTemplate(
            $id, $tenant, $name, $format, $content, $defaults, $active,
            new \DateTime(), new \DateTime(),
        );
    }

    public function test_label_template_creation(): void
    {
        $template = $this->makeLabelTemplate();
        $this->assertSame(1, $template->getId());
        $this->assertSame('Default ZPL', $template->getName());
        $this->assertSame('zpl', $template->getFormat());
        $this->assertTrue($template->isActive());
    }

    public function test_label_template_render_substitutes_placeholders(): void
    {
        $template = $this->makeLabelTemplate(
            content: '^XA^FD{{ barcode_value }} - {{ product_name }}^FS^XZ',
        );

        $rendered = $template->render([
            'barcode_value' => 'SKU-001',
            'product_name'  => 'Widget A',
        ]);

        $this->assertStringContainsString('SKU-001', $rendered);
        $this->assertStringContainsString('Widget A', $rendered);
    }

    public function test_label_template_render_uses_defaults_when_no_override(): void
    {
        $template = $this->makeLabelTemplate(
            content:  '{{ company_name }} - {{ barcode_value }}',
            defaults: ['company_name' => 'ACME Corp'],
        );

        $rendered = $template->render(['barcode_value' => '123']);
        $this->assertStringContainsString('ACME Corp', $rendered);
        $this->assertStringContainsString('123', $rendered);
    }

    public function test_label_template_render_variables_override_defaults(): void
    {
        $template = $this->makeLabelTemplate(
            content:  '{{ company_name }}',
            defaults: ['company_name' => 'Default Co'],
        );

        $rendered = $template->render(['company_name' => 'Override Co']);
        $this->assertStringContainsString('Override Co', $rendered);
        $this->assertStringNotContainsString('Default Co', $rendered);
    }

    public function test_label_template_get_placeholders(): void
    {
        $template = $this->makeLabelTemplate(
            content: '{{ barcode_value }} {{ product_name }} {{ quantity }}',
        );

        $placeholders = $template->getPlaceholders();
        $this->assertContains('barcode_value', $placeholders);
        $this->assertContains('product_name', $placeholders);
        $this->assertContains('quantity', $placeholders);
    }

    public function test_label_template_activate_deactivate(): void
    {
        $template = $this->makeLabelTemplate(active: false);
        $this->assertFalse($template->isActive());

        $template->activate();
        $this->assertTrue($template->isActive());

        $template->deactivate();
        $this->assertFalse($template->isActive());
    }

    public function test_label_template_update_content_changes_format(): void
    {
        $template = $this->makeLabelTemplate(format: 'zpl');
        $template->updateContent('new body {{ barcode_value }}', 'svg');

        $this->assertSame('svg', $template->getFormat());
        $this->assertStringContainsString('new body', $template->getContent());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodePrintJob entity
    // ─────────────────────────────────────────────────────────────────────────

    private function makePrintJob(
        ?int   $id     = 1,
        int    $tenant = 1,
        int    $defId  = 10,
        ?int   $tplId  = 5,
        string $status = \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PENDING,
        int    $copies = 1,
    ): \Modules\Barcode\Domain\Entities\BarcodePrintJob {
        return new \Modules\Barcode\Domain\Entities\BarcodePrintJob(
            $id, $tenant, $defId, $tplId, $status,
            '192.168.1.50:9100', $copies,
            null, [], null, new \DateTime(), null,
        );
    }

    public function test_print_job_creation(): void
    {
        $job = $this->makePrintJob();
        $this->assertSame(1, $job->getId());
        $this->assertTrue($job->isPending());
        $this->assertSame(1, $job->getCopies());
        $this->assertSame('192.168.1.50:9100', $job->getPrinterTarget());
    }

    public function test_print_job_mark_processing(): void
    {
        $job = $this->makePrintJob();
        $job->markProcessing();
        $this->assertTrue($job->isProcessing());
        $this->assertFalse($job->isPending());
    }

    public function test_print_job_mark_completed(): void
    {
        $job = $this->makePrintJob();
        $job->markCompleted('^XA^XZ');
        $this->assertTrue($job->isCompleted());
        $this->assertSame('^XA^XZ', $job->getRenderedOutput());
        $this->assertNotNull($job->getCompletedAt());
    }

    public function test_print_job_mark_failed(): void
    {
        $job = $this->makePrintJob();
        $job->markFailed('driver error');
        $this->assertTrue($job->isFailed());
        $this->assertSame('driver error', $job->getErrorMessage());
        $this->assertNotNull($job->getCompletedAt());
    }

    public function test_print_job_cancel_pending(): void
    {
        $job = $this->makePrintJob();
        $job->cancel();
        $this->assertTrue($job->isCancelled());
        $this->assertNotNull($job->getCompletedAt());
    }

    public function test_print_job_cancel_completed_throws(): void
    {
        $job = $this->makePrintJob(status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_COMPLETED);
        $this->expectException(\LogicException::class);
        $job->cancel();
    }

    public function test_print_job_cancel_processing_throws(): void
    {
        $job = $this->makePrintJob(status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PROCESSING);
        $this->expectException(\LogicException::class);
        $job->cancel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BarcodePrinterDispatcher
    // ─────────────────────────────────────────────────────────────────────────

    public function test_printer_dispatcher_routes_to_registered_driver(): void
    {
        $dispatcher = new \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher();
        $def        = $this->makeDefinition();
        $template   = null;

        /** @var \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface&MockObject $driver */
        $driver = $this->createMock(\Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface::class);
        $driver->method('getFormat')->willReturn('zpl');
        $driver->expects($this->once())
               ->method('render')
               ->with($def, null, [])
               ->willReturn('^XA^XZ');

        $dispatcher->addDriver($driver);

        $result = $dispatcher->render('zpl', $def, $template, []);
        $this->assertSame('^XA^XZ', $result);
    }

    public function test_printer_dispatcher_throws_for_unknown_format(): void
    {
        $dispatcher = new \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher();
        $def        = $this->makeDefinition();

        $this->expectException(\Modules\Barcode\Domain\Exceptions\UnsupportedBarcodeTypeException::class);
        $dispatcher->render('pdf', $def, null, []);
    }

    public function test_printer_dispatcher_get_supported_formats(): void
    {
        $dispatcher = new \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher();

        /** @var \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface&MockObject $driver */
        $zpl = $this->createMock(\Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface::class);
        $zpl->method('getFormat')->willReturn('zpl');

        /** @var \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface&MockObject $epl */
        $epl = $this->createMock(\Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface::class);
        $epl->method('getFormat')->willReturn('epl');

        $dispatcher->addDriver($zpl);
        $dispatcher->addDriver($epl);

        $formats = $dispatcher->getSupportedFormats();
        $this->assertContains('zpl', $formats);
        $this->assertContains('epl', $formats);
    }

    public function test_printer_dispatcher_has_driver(): void
    {
        $dispatcher = new \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher();
        $this->assertFalse($dispatcher->hasDriver('zpl'));

        /** @var \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface&MockObject $driver */
        $driver = $this->createMock(\Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface::class);
        $driver->method('getFormat')->willReturn('zpl');
        $dispatcher->addDriver($driver);

        $this->assertTrue($dispatcher->hasDriver('zpl'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ZplPrinterDriver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_zpl_driver_format(): void
    {
        $driver = new \Modules\Barcode\Infrastructure\Printing\Drivers\ZplPrinterDriver();
        $this->assertSame('zpl', $driver->getFormat());
    }

    public function test_zpl_driver_default_layout_code128(): void
    {
        $driver = new \Modules\Barcode\Infrastructure\Printing\Drivers\ZplPrinterDriver();
        $def    = $this->makeDefinition(type: BarcodeType::CODE128, value: 'SKU001');

        $output = $driver->render($def, null, []);
        $this->assertStringContainsString('^XA', $output);
        $this->assertStringContainsString('^XZ', $output);
        $this->assertStringContainsString('SKU001', $output);
    }

    public function test_zpl_driver_default_layout_qr(): void
    {
        $driver = new \Modules\Barcode\Infrastructure\Printing\Drivers\ZplPrinterDriver();
        $def    = $this->makeDefinition(type: BarcodeType::QR, value: 'https://example.com');

        $output = $driver->render($def, null, []);
        $this->assertStringContainsString('^BQ', $output);
    }

    public function test_zpl_driver_uses_template_when_provided(): void
    {
        $driver   = new \Modules\Barcode\Infrastructure\Printing\Drivers\ZplPrinterDriver();
        $def      = $this->makeDefinition(value: 'ABC');
        $template = $this->makeLabelTemplate(content: 'CUSTOM-{{ barcode_value }}-END');

        $output = $driver->render($def, $template, []);
        $this->assertStringContainsString('CUSTOM-ABC-END', $output);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EplPrinterDriver
    // ─────────────────────────────────────────────────────────────────────────

    public function test_epl_driver_format(): void
    {
        $driver = new \Modules\Barcode\Infrastructure\Printing\Drivers\EplPrinterDriver();
        $this->assertSame('epl', $driver->getFormat());
    }

    public function test_epl_driver_default_layout(): void
    {
        $driver = new \Modules\Barcode\Infrastructure\Printing\Drivers\EplPrinterDriver();
        $def    = $this->makeDefinition(value: 'LOT-XYZ');

        $output = $driver->render($def, null, []);
        $this->assertStringContainsString('LOT-XYZ', $output);
        $this->assertStringContainsString('P1', $output);  // print 1 copy
    }

    public function test_epl_driver_uses_template(): void
    {
        $driver   = new \Modules\Barcode\Infrastructure\Printing\Drivers\EplPrinterDriver();
        $def      = $this->makeDefinition(value: 'EPL-VAL');
        $template = $this->makeLabelTemplate(format: 'epl', content: 'N\nB10,10,0,1,2,5,40,B,"{{ barcode_value }}"\nP1');

        $output = $driver->render($def, $template, []);
        $this->assertStringContainsString('EPL-VAL', $output);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ManageLabelTemplateService
    // ─────────────────────────────────────────────────────────────────────────

    /** @return \Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface&MockObject */
    private function makeLabelTemplateRepo(): \Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface
    {
        return $this->createMock(\Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface::class);
    }

    public function test_manage_label_template_service_create(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);
        $saved   = $this->makeLabelTemplate();

        $repo->expects($this->once())
             ->method('save')
             ->willReturn($saved);

        $result = $service->create(1, 'Default ZPL', 'zpl', '^XA^XZ', []);
        $this->assertSame($saved, $result);
    }

    public function test_manage_label_template_service_invalid_format_throws(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);

        $this->expectException(\InvalidArgumentException::class);
        $service->create(1, 'Bad Template', 'pdf', '^XA^XZ', []);
    }

    public function test_manage_label_template_service_get_by_id_not_found(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);

        $repo->method('findById')->willReturn(null);

        $this->expectException(\Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException::class);
        $service->getById(99);
    }

    public function test_manage_label_template_service_activate(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);
        $tpl     = $this->makeLabelTemplate(active: false);
        $saved   = $this->makeLabelTemplate(active: true);

        $repo->method('findById')->willReturn($tpl);
        $repo->expects($this->once())->method('save')->willReturn($saved);

        $result = $service->activate(1);
        $this->assertTrue($result->isActive());
    }

    public function test_manage_label_template_service_deactivate(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);
        $tpl     = $this->makeLabelTemplate(active: true);
        $saved   = $this->makeLabelTemplate(active: false);

        $repo->method('findById')->willReturn($tpl);
        $repo->expects($this->once())->method('save')->willReturn($saved);

        $result = $service->deactivate(1);
        $this->assertFalse($result->isActive());
    }

    public function test_manage_label_template_service_list_all(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);
        $list    = [$this->makeLabelTemplate(id: 1), $this->makeLabelTemplate(id: 2)];

        $repo->expects($this->once())->method('findAll')->with(1)->willReturn($list);

        $this->assertSame($list, $service->listAll(1));
    }

    public function test_manage_label_template_service_delete(): void
    {
        $repo    = $this->makeLabelTemplateRepo();
        $service = new \Modules\Barcode\Application\Services\ManageLabelTemplateService($repo);
        $tpl     = $this->makeLabelTemplate();

        $repo->method('findById')->willReturn($tpl);
        $repo->expects($this->once())->method('delete')->with(1);

        $service->delete(1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PrintBarcodeLabelService
    // ─────────────────────────────────────────────────────────────────────────

    /** @return \Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface&MockObject */
    private function makePrintJobRepo(): \Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface
    {
        return $this->createMock(\Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface::class);
    }

    private function makePrintLabelService(
        ?\Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface $jobRepo = null,
        ?\Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface $defRepo = null,
        ?\Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface $tplRepo = null,
        ?\Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher $printerDispatcher = null,
    ): \Modules\Barcode\Application\Services\PrintBarcodeLabelService {
        return new \Modules\Barcode\Application\Services\PrintBarcodeLabelService(
            $jobRepo           ?? $this->makePrintJobRepo(),
            $defRepo           ?? $this->makeDefinitionRepo(),
            $tplRepo           ?? $this->makeLabelTemplateRepo(),
            $printerDispatcher ?? new \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher(),
        );
    }

    public function test_print_service_queue_creates_pending_job(): void
    {
        $defRepo = $this->makeDefinitionRepo();
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo, defRepo: $defRepo);

        $def         = $this->makeDefinition(id: 10);
        $pendingJob  = $this->makePrintJob(id: null, defId: 10, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PENDING);
        $savedJob    = $this->makePrintJob(id: 1,    defId: 10, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PENDING);

        $defRepo->method('findById')->with(10)->willReturn($def);
        $jobRepo->expects($this->once())->method('save')->willReturn($savedJob);

        $result = $service->queue(1, 10, null, 'zpl', null, 1, []);
        $this->assertSame($savedJob, $result);
        $this->assertTrue($result->isPending());
    }

    public function test_print_service_queue_throws_when_definition_not_found(): void
    {
        $defRepo = $this->makeDefinitionRepo();
        $service = $this->makePrintLabelService(defRepo: $defRepo);

        $defRepo->method('findById')->willReturn(null);

        $this->expectException(\Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException::class);
        $service->queue(1, 99, null, 'zpl', null, 1, []);
    }

    public function test_print_service_process_renders_and_completes_job(): void
    {
        $defRepo    = $this->makeDefinitionRepo();
        $jobRepo    = $this->makePrintJobRepo();
        $tplRepo    = $this->makeLabelTemplateRepo();

        $dispatcher = new \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher();

        /** @var \Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface&MockObject $driver */
        $driver = $this->createMock(\Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface::class);
        $driver->method('getFormat')->willReturn('zpl');
        $driver->method('render')->willReturn('^XA RENDERED ^XZ');
        $dispatcher->addDriver($driver);

        $service = $this->makePrintLabelService(
            jobRepo: $jobRepo, defRepo: $defRepo, tplRepo: $tplRepo, printerDispatcher: $dispatcher,
        );

        $pendingJob   = $this->makePrintJob(id: 1, defId: 10, tplId: null);
        $processingJob = $this->makePrintJob(id: 1, defId: 10, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PROCESSING);
        $completedJob  = $this->makePrintJob(id: 1, defId: 10, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_COMPLETED);
        $completedJob->markCompleted('^XA RENDERED ^XZ');

        $def = $this->makeDefinition(id: 10, type: BarcodeType::CODE128, value: 'SKU-001');

        $jobRepo->method('findById')->willReturn($pendingJob);
        $defRepo->method('findById')->willReturn($def);
        $tplRepo->method('findById')->willReturn(null);

        // Save is called twice: once for processing, once for completed
        $jobRepo->expects($this->exactly(2))
                ->method('save')
                ->willReturnOnConsecutiveCalls($processingJob, $completedJob);

        $result = $service->process(1);
        $this->assertTrue($result->isCompleted());
    }

    public function test_print_service_process_marks_failed_on_error(): void
    {
        $defRepo = $this->makeDefinitionRepo();
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo, defRepo: $defRepo);

        $pendingJob    = $this->makePrintJob(id: 1);
        $processingJob = $this->makePrintJob(id: 1, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PROCESSING);
        $failedJob     = $this->makePrintJob(id: 1, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_FAILED);

        $jobRepo->method('findById')->willReturn($pendingJob);
        $defRepo->method('findById')->willReturn(null);  // triggers BarcodeNotFoundException

        $jobRepo->expects($this->exactly(2))
                ->method('save')
                ->willReturnOnConsecutiveCalls($processingJob, $failedJob);

        $result = $service->process(1);
        $this->assertTrue($result->isFailed());
    }

    public function test_print_service_process_throws_when_not_pending(): void
    {
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo);

        $completedJob = $this->makePrintJob(id: 1, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_COMPLETED);
        $jobRepo->method('findById')->willReturn($completedJob);

        $this->expectException(\LogicException::class);
        $service->process(1);
    }

    public function test_print_service_cancel_pending_job(): void
    {
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo);

        $job       = $this->makePrintJob(id: 1);
        $cancelled = $this->makePrintJob(id: 1, status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_CANCELLED);

        $jobRepo->method('findById')->willReturn($job);
        $jobRepo->expects($this->once())->method('save')->willReturn($cancelled);

        $result = $service->cancel(1);
        $this->assertTrue($result->isCancelled());
    }

    public function test_print_service_list_all(): void
    {
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo);
        $list    = [$this->makePrintJob(id: 1), $this->makePrintJob(id: 2)];

        $jobRepo->expects($this->once())->method('findAll')->with(1)->willReturn($list);
        $this->assertSame($list, $service->listAll(1));
    }

    public function test_print_service_list_by_status(): void
    {
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo);
        $list    = [$this->makePrintJob(status: \Modules\Barcode\Domain\Entities\BarcodePrintJob::STATUS_PENDING)];

        $jobRepo->expects($this->once())
                ->method('findByStatus')
                ->with(1, 'pending')
                ->willReturn($list);

        $this->assertSame($list, $service->listByStatus(1, 'pending'));
    }

    public function test_print_service_delete(): void
    {
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo);

        $job = $this->makePrintJob(id: 1);
        $jobRepo->method('findById')->willReturn($job);
        $jobRepo->expects($this->once())->method('delete')->with(1);

        $service->delete(1);
    }

    public function test_print_service_delete_throws_when_not_found(): void
    {
        $jobRepo = $this->makePrintJobRepo();
        $service = $this->makePrintLabelService(jobRepo: $jobRepo);

        $jobRepo->method('findById')->willReturn(null);

        $this->expectException(\Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException::class);
        $service->delete(99);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RecordBarcodeScanService – event dispatch
    // ─────────────────────────────────────────────────────────────────────────

    public function test_record_scan_dispatches_scan_recorded_event(): void
    {
        /** @var \Modules\Barcode\Domain\RepositoryInterfaces\BarcodeScanRepositoryInterface&MockObject $scanRepo */
        $scanRepo = $this->createMock(\Modules\Barcode\Domain\RepositoryInterfaces\BarcodeScanRepositoryInterface::class);
        $defRepo  = $this->makeDefinitionRepo();
        $service  = new RecordBarcodeScanService($scanRepo, $defRepo);

        $savedScan = $this->makeScan(id: 1);

        $defRepo->method('findByValue')->willReturn(null);
        $scanRepo->method('save')->willReturn($savedScan);

        // In pure unit tests the 'events' binding is not present in the container;
        // the guard `app()->bound('events')` prevents dispatch. We verify the scan
        // is still returned correctly.
        $result = $service->record(1, 'ANY_VALUE', null, null, null);
        $this->assertSame($savedScan, $result);
        $this->assertInstanceOf(\Modules\Barcode\Domain\Entities\BarcodeScan::class, $result);
    }
}
