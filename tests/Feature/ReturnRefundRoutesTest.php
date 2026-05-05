<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ReturnRefundRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassportKeys();
    }

    public function test_return_refund_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/return-refund/process', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    private function preparePassportKeys(): void
    {
        if (self::$passportKeysPrepared) {
            return;
        }

        Artisan::call('passport:keys', ['--force' => true]);

        self::$passportKeysPrepared = true;
    }
}
