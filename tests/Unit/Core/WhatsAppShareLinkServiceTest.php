<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use InvalidArgumentException;
use Modules\Core\Services\WhatsAppShareLinkService;
use Tests\TestCase;

final class WhatsAppShareLinkServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('document-sharing.whatsapp.default_country_calling_code', '94');
    }

    public function test_it_normalizes_local_and_international_numbers_for_whatsapp(): void
    {
        $service = app(WhatsAppShareLinkService::class);

        self::assertSame('94771234567', $service->normalize('077 123 4567'));
        self::assertSame('94771234567', $service->normalize('+94 (77) 123-4567'));
        self::assertSame('94771234567', $service->normalize('0094 77 123 4567'));
        self::assertSame('94771234567', $service->normalize('94771234567'));
    }

    public function test_it_builds_an_encoded_wa_me_message(): void
    {
        $result = app(WhatsAppShareLinkService::class)->create(
            '0771234567',
            "Invoice INV-1\nhttps://erp.example.test/shared/invoice",
        );

        self::assertSame('94771234567', $result['phone']);
        self::assertStringStartsWith('https://wa.me/94771234567?text=', $result['whatsapp_url']);
        self::assertStringContainsString('Invoice%20INV-1%0Ahttps%3A%2F%2Ferp.example.test', $result['whatsapp_url']);
    }

    public function test_it_rejects_invalid_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid WhatsApp number');

        app(WhatsAppShareLinkService::class)->normalize('123');
    }
}
