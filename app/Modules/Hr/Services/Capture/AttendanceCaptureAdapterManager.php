<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services\Capture;

use InvalidArgumentException;
use Modules\Hr\Contracts\AttendanceCaptureAdapterInterface;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Services\Capture\Adapters\BarcodeCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\BiometricCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\FileUploadCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\ManualEntryCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\MobileGpsCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\NfcTagCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\RfidCardCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\TotpQrCaptureAdapter;
use Modules\Hr\Services\Capture\Adapters\WebPortalCaptureAdapter;

final class AttendanceCaptureAdapterManager
{
    /** @var array<string, class-string<AttendanceCaptureAdapterInterface>> */
    private array $customAdapters = [];

    public function resolve(CaptureMethod $method): AttendanceCaptureAdapterInterface
    {
        if (isset($this->customAdapters[$method->value])) {
            return app($this->customAdapters[$method->value]);
        }

        return match ($method) {
            CaptureMethod::WebPortal => app(WebPortalCaptureAdapter::class),
            CaptureMethod::MobileGps => app(MobileGpsCaptureAdapter::class),
            CaptureMethod::RfidCard => app(RfidCardCaptureAdapter::class),
            CaptureMethod::NfcTag => app(NfcTagCaptureAdapter::class),
            CaptureMethod::Barcode => app(BarcodeCaptureAdapter::class),
            CaptureMethod::TotpQr => app(TotpQrCaptureAdapter::class),
            CaptureMethod::ManualAdmin => app(ManualEntryCaptureAdapter::class),
            CaptureMethod::Biometric => app(BiometricCaptureAdapter::class),
            CaptureMethod::FileUpload => app(FileUploadCaptureAdapter::class),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function capture(int $tenantId, CaptureMethod $method, array $payload): HrAttendancePunch
    {
        return $this->resolve($method)->capture($tenantId, $payload);
    }

    /**
     * Register a custom capture adapter for extensibility.
     *
     * @param  class-string<AttendanceCaptureAdapterInterface>  $adapterClass
     */
    public function registerAdapter(CaptureMethod|string $method, string $adapterClass): void
    {
        $key = $method instanceof CaptureMethod ? $method->value : $method;

        if (! is_subclass_of($adapterClass, AttendanceCaptureAdapterInterface::class)) {
            throw new InvalidArgumentException("Class {$adapterClass} must implement ".AttendanceCaptureAdapterInterface::class);
        }

        $this->customAdapters[$key] = $adapterClass;
    }

    /**
     * @return list<string>
     */
    public function supportedMethods(): array
    {
        return array_values(array_unique(array_merge(
            array_map(static fn (CaptureMethod $m): string => $m->value, CaptureMethod::cases()),
            array_keys($this->customAdapters),
        )));
    }
}
