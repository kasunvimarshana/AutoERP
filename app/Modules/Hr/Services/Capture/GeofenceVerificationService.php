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

use Modules\Hr\Models\HrAttendanceGeofence;

final class GeofenceVerificationService
{
    private const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * Calculate Haversine distance in meters between two GPS coordinates.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDistance = deg2rad($lat2 - $lat1);
        $lonDistance = deg2rad($lon2 - $lon1);

        $a = sin($latDistance / 2) * sin($latDistance / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lonDistance / 2) * sin($lonDistance / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check if coordinates fall within geofence radius (with optional device accuracy tolerance).
     */
    public function isWithinGeofence(
        HrAttendanceGeofence $geofence,
        float $latitude,
        float $longitude,
        int $accuracyMeters = 0,
    ): bool {
        $distance = $this->calculateDistance(
            (float) $geofence->latitude,
            (float) $geofence->longitude,
            $latitude,
            $longitude,
        );

        $allowedRadius = (int) $geofence->radius_meters + max(0, min($accuracyMeters, 50));

        return $distance <= $allowedRadius;
    }

    /**
     * Check if client IP address is whitelisted in geofence rules.
     */
    public function isIpWhitelisted(HrAttendanceGeofence $geofence, ?string $clientIp): bool
    {
        if ($clientIp === null || empty($geofence->ip_whitelist)) {
            return true;
        }

        $allowedEntries = array_filter(
            array_map('trim', explode(',', (string) $geofence->ip_whitelist)),
        );

        if (empty($allowedEntries)) {
            return true;
        }

        foreach ($allowedEntries as $entry) {
            if ($entry === $clientIp) {
                return true;
            }

            if (str_contains($entry, '/') && $this->cidrMatch($clientIp, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate IPv4 CIDR subnet match.
     */
    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);
        if (! filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $maskInt = (int) $mask;
        if ($maskInt < 0 || $maskInt > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $netmask = ~((1 << (32 - $maskInt)) - 1);

        return ($ipLong & $netmask) === ($subnetLong & $netmask);
    }
}
