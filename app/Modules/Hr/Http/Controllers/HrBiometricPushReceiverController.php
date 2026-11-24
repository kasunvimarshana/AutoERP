<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Biometric\Adapters\UsbImportAdapter;
use Modules\Hr\Biometric\Adapters\ZkTecoPushAdapter;
use Modules\Hr\Biometric\Services\BiometricAdapterManager;
use Modules\Hr\Biometric\Services\BiometricLogIngestionService;
use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Models\HrBiometricDevice;
use Symfony\Component\HttpFoundation\Response;

final class HrBiometricPushReceiverController
{
    public function __construct(
        private readonly BiometricAdapterManager $adapterManager,
        private readonly BiometricLogIngestionService $ingestionService,
    ) {}

    /**
     * ZKTeco ADMS / iClock cdata endpoint (GET handshake, POST punch logs).
     */
    public function zkTecoCdata(Request $request): Response
    {
        $sn = (string) $request->query('SN');
        if ($sn === '') {
            return new LaravelResponse("ERROR: Missing SN\n", 400, ['Content-Type' => 'text/plain']);
        }

        $device = HrBiometricDevice::query()
            ->where('serial_number', $sn)
            ->where('is_enabled', true)
            ->first();

        if ($device === null) {
            // Auto-register device or return 404
            return new LaravelResponse("ERROR: Unknown Device SN {$sn}\n", 404, ['Content-Type' => 'text/plain']);
        }

        /** @var ZkTecoPushAdapter $adapter */
        $adapter = $this->adapterManager->resolve($device);

        // Update heartbeat
        $device->update([
            'last_ping_at' => CarbonImmutable::now(),
            'status' => BiometricDeviceStatus::Active,
        ]);

        if ($request->isMethod('GET')) {
            return $adapter->generateHandshakeResponse($device);
        }

        // POST ATTLOG
        $syncResult = $adapter->parseIncomingPush($device, $request);

        if ($syncResult->isSuccess && count($syncResult->punches) > 0) {
            $this->ingestionService->ingest($device, $syncResult->punches);
        }

        return $adapter->generatePushResponse($device, $request, $syncResult);
    }

    /**
     * ZKTeco ADMS getrequest endpoint (Command Polling).
     */
    public function zkTecoGetRequest(Request $request): Response
    {
        $sn = (string) $request->query('SN');
        $device = HrBiometricDevice::query()->where('serial_number', $sn)->first();

        if ($device === null) {
            return new LaravelResponse("OK\n", 200, ['Content-Type' => 'text/plain']);
        }

        /** @var ZkTecoPushAdapter $adapter */
        $adapter = $this->adapterManager->resolve($device);

        return $adapter->generateCommandResponse($device);
    }

    /**
     * ZKTeco ADMS devicecmd endpoint (Command ACK).
     */
    public function zkTecoDeviceCmd(Request $request): Response
    {
        return new LaravelResponse("OK\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Hikvision ISAPI Event Notification Webhook.
     */
    public function hikvisionPush(Request $request): JsonResponse
    {
        $clientIp = $request->ip();
        $device = HrBiometricDevice::query()
            ->where('ip_address', $clientIp)
            ->orWhere('serial_number', (string) $request->input('serial_number'))
            ->first();

        if ($device === null) {
            return response()->json(['error' => 'Device not recognized'], 404);
        }

        $adapter = $this->adapterManager->resolve($device);
        $syncResult = $adapter->parseIncomingPush($device, $request);

        if ($syncResult->isSuccess && count($syncResult->punches) > 0) {
            $this->ingestionService->ingest($device, $syncResult->punches);
        }

        return response()->json([
            'status' => 'OK',
            'received' => $syncResult->fetchedCount,
        ]);
    }

    /**
     * Generic IoT JSON Webhook Push Endpoint.
     */
    public function genericWebhook(Request $request, string $deviceCode): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('device_code', $deviceCode)
            ->where('is_enabled', true)
            ->first();

        if ($device === null) {
            return response()->json(['error' => 'Device not found or disabled.'], 404);
        }

        $adapter = $this->adapterManager->resolve($device);
        $syncResult = $adapter->parseIncomingPush($device, $request);

        if (! $syncResult->isSuccess) {
            return response()->json(['error' => $syncResult->errorMessage], 401);
        }

        if (count($syncResult->punches) > 0) {
            $stats = $this->ingestionService->ingest($device, $syncResult->punches);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        }

        return response()->json([
            'success' => true,
            'received' => 0,
        ]);
    }

    /**
     * Offline USB Flash / File Log Upload.
     */
    public function uploadLog(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $adapter = app(UsbImportAdapter::class);
        $syncResult = $adapter->parseIncomingPush($device, $request);

        if (! $syncResult->isSuccess) {
            return response()->json(['error' => $syncResult->errorMessage], 422);
        }

        $stats = $this->ingestionService->ingest($device, $syncResult->punches);

        return response()->json([
            'message' => "Successfully imported {$stats['processed']} punches from file ({$stats['duplicates']} duplicates ignored, {$stats['unmapped']} unmapped).",
            'stats' => $stats,
        ]);
    }
}
