<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Unit\Hr;

use Illuminate\Http\Request;
use Modules\Hr\Biometric\Adapters\GenericWebhookAdapter;
use Modules\Hr\Biometric\Adapters\UsbImportAdapter;
use Modules\Hr\Biometric\Adapters\ZkTecoPushAdapter;
use Modules\Hr\Biometric\Services\BiometricAdapterManager;
use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Enums\BiometricProtocol;
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Models\HrBiometricDevice;
use Tests\TestCase;

final class BiometricAdapterTest extends TestCase
{
    public function test_adapter_manager_resolves_all_supported_protocols(): void
    {
        $manager = app(BiometricAdapterManager::class);

        foreach (BiometricProtocol::cases() as $protocol) {
            $adapter = $manager->resolveByProtocol($protocol);
            self::assertNotNull($adapter);
        }
    }

    public function test_zkteco_push_adapter_parses_tab_delimited_attlog_payload(): void
    {
        $adapter = app(ZkTecoPushAdapter::class);
        $device = (new HrBiometricDevice)->forceFill([
            'id' => 1,
            'tenant_id' => 1,
            'device_code' => 'ZK-001',
            'serial_number' => 'ZK12345678',
            'protocol' => BiometricProtocol::ZkTecoPush,
            'status' => BiometricDeviceStatus::Active,
        ]);

        $content = "101\t2026-11-25 08:30:00\t0\t1\t0\t0\t0\r\n"
            ."102\t2026-11-25 08:31:15\t1\t15\t0\t0\t0\r\n"
            ."103\t2026-11-25 12:05:00\t2\t4\t0\t0\t0\r\n";

        $request = Request::create('/iclock/cdata', 'POST', [], [], [], [], $content);
        $result = $adapter->parseIncomingPush($device, $request);

        self::assertTrue($result->isSuccess);
        self::assertCount(3, $result->punches);

        // Punch 1: Check-in, Fingerprint
        self::assertSame('101', $result->punches[0]->biometricUserId);
        self::assertSame('2026-11-25 08:30:00', $result->punches[0]->punchTime);
        self::assertSame(BiometricPunchState::CheckIn, $result->punches[0]->punchState);
        self::assertSame(BiometricVerifyMethod::Fingerprint, $result->punches[0]->verifyMethod);

        // Punch 2: Check-out, Face
        self::assertSame('102', $result->punches[1]->biometricUserId);
        self::assertSame(BiometricPunchState::CheckOut, $result->punches[1]->punchState);
        self::assertSame(BiometricVerifyMethod::Face, $result->punches[1]->verifyMethod);

        // Punch 3: Break-out, Card
        self::assertSame('103', $result->punches[2]->biometricUserId);
        self::assertSame(BiometricPunchState::BreakOut, $result->punches[2]->punchState);
        self::assertSame(BiometricVerifyMethod::Card, $result->punches[2]->verifyMethod);
    }

    public function test_zkteco_push_adapter_generates_handshake_and_push_response(): void
    {
        $adapter = app(ZkTecoPushAdapter::class);
        $device = (new HrBiometricDevice)->forceFill([
            'id' => 1,
            'tenant_id' => 1,
            'serial_number' => 'ZK999',
            'protocol' => BiometricProtocol::ZkTecoPush,
        ]);

        $handshake = $adapter->generateHandshakeResponse($device);
        self::assertSame(200, $handshake->getStatusCode());
        self::assertStringContainsString('GET OPTION FROM: ZK999', (string) $handshake->getContent());
        self::assertStringContainsString('Stamp=', (string) $handshake->getContent());

        $content = "101\t2026-11-25 08:30:00\t0\t1\t0\t0\t0\n";
        $request = Request::create('/iclock/cdata', 'POST', [], [], [], [], $content);
        $syncResult = $adapter->parseIncomingPush($device, $request);

        $response = $adapter->generatePushResponse($device, $request, $syncResult);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame("OK: 1\n", (string) $response->getContent());
    }

    public function test_generic_webhook_adapter_validates_hmac_and_parses_json(): void
    {
        $adapter = app(GenericWebhookAdapter::class);
        $secret = 'super-secret-hmac-key';
        $device = (new HrBiometricDevice)->forceFill([
            'id' => 1,
            'tenant_id' => 1,
            'device_code' => 'IOT-WEBHOOK',
            'auth_token' => $secret,
            'protocol' => BiometricProtocol::GenericWebhook,
        ]);

        $payload = json_encode([
            'punches' => [
                [
                    'user_id' => 'EMP-555',
                    'punch_time' => '2026-11-25T09:00:00+05:30',
                    'state' => 'check_in',
                    'verify_method' => 'face',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $payload, $secret);

        $request = Request::create(
            '/api/v1/hr/biometric/push/webhook/IOT-WEBHOOK',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_BIOMETRIC_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload,
        );

        $result = $adapter->parseIncomingPush($device, $request);
        self::assertTrue($result->isSuccess);
        self::assertCount(1, $result->punches);
        self::assertSame('EMP-555', $result->punches[0]->biometricUserId);
        self::assertSame(BiometricPunchState::CheckIn, $result->punches[0]->punchState);
        self::assertSame(BiometricVerifyMethod::Face, $result->punches[0]->verifyMethod);
    }

    public function test_generic_webhook_adapter_rejects_invalid_hmac(): void
    {
        $adapter = app(GenericWebhookAdapter::class);
        $device = (new HrBiometricDevice)->forceFill([
            'id' => 1,
            'tenant_id' => 1,
            'device_code' => 'IOT-WEBHOOK',
            'auth_token' => 'correct-secret',
            'protocol' => BiometricProtocol::GenericWebhook,
        ]);

        $payload = '{"punches":[]}';
        $invalidSignature = 'wrong-signature';

        $request = Request::create(
            '/api/v1/hr/biometric/push/webhook/IOT-WEBHOOK',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_BIOMETRIC_SIGNATURE' => $invalidSignature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload,
        );

        $result = $adapter->parseIncomingPush($device, $request);
        self::assertFalse($result->isSuccess);
        self::assertStringContainsString('Unauthorized', (string) $result->errorMessage);
    }

    public function test_usb_import_adapter_parses_csv_lines(): void
    {
        $adapter = app(UsbImportAdapter::class);
        $device = (new HrBiometricDevice)->forceFill([
            'id' => 1,
            'tenant_id' => 1,
            'protocol' => BiometricProtocol::UsbImport,
        ]);

        $csv = "PIN,Time,State,Verify\n"
            ."888,2026-11-25 17:00:00,1,1\n"
            ."889,2026-11-25 17:05:00,1,4\n";

        $request = Request::create('/upload', 'POST', [], [], [], [], $csv);
        $result = $adapter->parseIncomingPush($device, $request);

        self::assertTrue($result->isSuccess);
        self::assertCount(2, $result->punches);
        self::assertSame('888', $result->punches[0]->biometricUserId);
        self::assertSame(BiometricPunchState::CheckOut, $result->punches[0]->punchState);
        self::assertSame('889', $result->punches[1]->biometricUserId);
        self::assertSame(BiometricVerifyMethod::Card, $result->punches[1]->verifyMethod);
    }
}
