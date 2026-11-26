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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Modules\Core\Contracts\TenantExecutionContextInterface;
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
        private readonly TenantExecutionContextInterface $tenantContext,
    ) {}

    /**
     * ZKTeco ADMS / iClock cdata endpoint (GET handshake, POST punch logs).
     */
    public function zkTecoCdata(Request $request, ?int $tenant = null): Response
    {
        $sn = trim((string) $request->query('SN'));
        if ($sn === '') {
            return new LaravelResponse("ERROR: Missing SN\n", 400, ['Content-Type' => 'text/plain']);
        }

        $resolution = $this->resolveZkTecoDevice($request, $sn, $tenant);
        if ($resolution instanceof Response) {
            return $resolution;
        }
        /** @var HrBiometricDevice $device */
        $device = $resolution;

        return $this->tenantContext->runForTenant((int) $device->tenant_id, function () use ($device, $request): Response {
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
        });
    }

    /**
     * ZKTeco ADMS getrequest endpoint (Command Polling).
     */
    public function zkTecoGetRequest(Request $request, ?int $tenant = null): Response
    {
        $sn = trim((string) $request->query('SN'));
        if ($sn === '') {
            return new LaravelResponse("OK\n", 200, ['Content-Type' => 'text/plain']);
        }

        $resolution = $this->resolveZkTecoDevice($request, $sn, $tenant);
        if ($resolution instanceof Response) {
            return $resolution->getStatusCode() === 404
                ? new LaravelResponse("OK\n", 200, ['Content-Type' => 'text/plain'])
                : $resolution;
        }
        $device = $resolution;

        /** @var ZkTecoPushAdapter $adapter */
        $adapter = $this->adapterManager->resolve($device);

        return $adapter->generateCommandResponse($device);
    }

    private function resolveZkTecoDevice(Request $request, string $sn, ?int $tenant): HrBiometricDevice|Response
    {
        $tenantId = $this->extractTenantId($request, $tenant);

        return $this->tenantContext->runAsControlPlane(function () use ($request, $sn, $tenantId): HrBiometricDevice|Response {
            $query = HrBiometricDevice::query()
                ->where('serial_number', $sn)
                ->where('is_enabled', true);

            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }

            return $this->resolvePushDeviceCandidate(
                query: $query,
                request: $request,
                notFoundMessage: "ERROR: Unknown Device SN {$sn}\n",
                ambiguousMessage: "ERROR: Ambiguous Device SN {$sn}; tenant or device token required\n",
                plainTextResponse: true,
            );
        });
    }

    private function extractTenantId(Request $request, ?int $tenant): ?int
    {
        $queryTenant = $request->query('tenant_id') ?? $request->input('tenant_id');

        return $tenant ?? (is_numeric($queryTenant) && (int) $queryTenant > 0 ? (int) $queryTenant : null);
    }

    private function extractDeviceToken(Request $request): string
    {
        return trim((string) (
            $request->query('token')
            ?? $request->header('X-Device-Token')
            ?? $request->bearerToken()
            ?? ''
        ));
    }

    private function resolvePushDeviceCandidate(
        Builder $query,
        Request $request,
        string $notFoundMessage,
        string $ambiguousMessage,
        bool $plainTextResponse = false,
    ): HrBiometricDevice|Response {
        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            return $plainTextResponse
                ? new LaravelResponse($notFoundMessage, 404, ['Content-Type' => 'text/plain'])
                : response()->json(['error' => $notFoundMessage], 404);
        }

        $token = $this->extractDeviceToken($request);
        $signature = trim((string) ($request->header('X-Biometric-Signature') ?? ''));

        if ($candidates->count() > 1) {
            if ($token !== '') {
                $tokenMatches = $candidates->filter(
                    static fn (HrBiometricDevice $candidate): bool => $candidate->auth_token !== null
                        && $candidate->auth_token !== ''
                        && hash_equals((string) $candidate->auth_token, $token),
                );

                if ($tokenMatches->count() === 1) {
                    /** @var HrBiometricDevice $matched */
                    $matched = $tokenMatches->first();

                    return $matched;
                }
            }

            if ($signature !== '') {
                $rawBody = (string) $request->getContent();
                $sigMatches = $candidates->filter(
                    static fn (HrBiometricDevice $candidate): bool => $candidate->auth_token !== null
                        && $candidate->auth_token !== ''
                        && hash_equals(hash_hmac('sha256', $rawBody, (string) $candidate->auth_token), $signature),
                );

                if ($sigMatches->count() === 1) {
                    /** @var HrBiometricDevice $matched */
                    $matched = $sigMatches->first();

                    return $matched;
                }
            }

            return $plainTextResponse
                ? new LaravelResponse($ambiguousMessage, 409, ['Content-Type' => 'text/plain'])
                : response()->json(['error' => $ambiguousMessage], 409);
        }

        /** @var HrBiometricDevice $device */
        $device = $candidates->first();

        if ($device->auth_token !== null && $device->auth_token !== '' && $token !== '' && ! hash_equals((string) $device->auth_token, $token)) {
            return $plainTextResponse
                ? new LaravelResponse("ERROR: Invalid Device Token\n", 401, ['Content-Type' => 'text/plain'])
                : response()->json(['error' => 'Invalid device token.'], 401);
        }

        return $device;
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
    public function hikvisionPush(Request $request, ?int $tenant = null): JsonResponse
    {
        $tenantId = $this->extractTenantId($request, $tenant);
        $serialNumber = trim((string) ($request->input('serial_number') ?? $request->query('serial_number') ?? ''));
        $clientIp = trim((string) $request->ip());

        if ($serialNumber === '' && $clientIp === '') {
            return response()->json(['error' => 'Device not recognized'], 404);
        }

        $resolution = $this->tenantContext->runAsControlPlane(function () use ($request, $tenantId, $serialNumber, $clientIp): HrBiometricDevice|JsonResponse {
            $query = HrBiometricDevice::query()
                ->where('is_enabled', true);

            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }

            $query->where(function (Builder $sub) use ($serialNumber, $clientIp): void {
                if ($serialNumber !== '') {
                    $sub->where('serial_number', $serialNumber);
                    if ($clientIp !== '') {
                        $sub->orWhere('ip_address', $clientIp);
                    }
                } else {
                    $sub->where('ip_address', $clientIp);
                }
            });

            return $this->resolvePushDeviceCandidate(
                query: $query,
                request: $request,
                notFoundMessage: 'Device not recognized',
                ambiguousMessage: 'Ambiguous device identifier; tenant or device token required.',
            );
        });

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }
        /** @var HrBiometricDevice $device */
        $device = $resolution;

        return $this->tenantContext->runForTenant((int) $device->tenant_id, function () use ($device, $request): JsonResponse {
            $adapter = $this->adapterManager->resolve($device);
            $syncResult = $adapter->parseIncomingPush($device, $request);

            if ($syncResult->isSuccess && count($syncResult->punches) > 0) {
                $this->ingestionService->ingest($device, $syncResult->punches);
            }

            return response()->json([
                'status' => 'OK',
                'received' => $syncResult->fetchedCount,
            ]);
        });
    }

    /**
     * Generic IoT JSON Webhook Push Endpoint (tenant-scoped route alias).
     */
    public function genericWebhookForTenant(Request $request, int $tenant, string $deviceCode): JsonResponse
    {
        return $this->genericWebhook($request, $deviceCode, $tenant);
    }

    /**
     * Generic IoT JSON Webhook Push Endpoint.
     */
    public function genericWebhook(Request $request, string $deviceCode, ?int $tenant = null): JsonResponse
    {
        $tenantId = $this->extractTenantId($request, $tenant);

        $resolution = $this->tenantContext->runAsControlPlane(function () use ($request, $tenantId, $deviceCode): HrBiometricDevice|JsonResponse {
            $query = HrBiometricDevice::query()
                ->where('device_code', $deviceCode)
                ->where('is_enabled', true);

            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }

            return $this->resolvePushDeviceCandidate(
                query: $query,
                request: $request,
                notFoundMessage: 'Device not found or disabled.',
                ambiguousMessage: 'Ambiguous device code; tenant or device token required.',
            );
        });

        if ($resolution instanceof JsonResponse) {
            return $resolution;
        }
        /** @var HrBiometricDevice $device */
        $device = $resolution;

        return $this->tenantContext->runForTenant((int) $device->tenant_id, function () use ($device, $request): JsonResponse {
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
        });
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
