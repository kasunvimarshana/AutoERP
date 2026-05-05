<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ServiceCenterRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_service_center_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/service-orders')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/service-orders', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $fakeId = '00000000-0000-0000-0000-000000000001';
        $this->getJson("/api/service-orders/{$fakeId}")->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson("/api/service-orders/{$fakeId}/complete", [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson("/api/service-orders/{$fakeId}/cancel", [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson("/api/service-orders/{$fakeId}/tasks")->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson("/api/service-orders/{$fakeId}/parts")->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
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
