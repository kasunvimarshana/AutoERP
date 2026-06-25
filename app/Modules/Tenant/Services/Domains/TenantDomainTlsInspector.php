<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use DateTimeImmutable;
use RuntimeException;

final class TenantDomainTlsInspector
{
    public function expiry(string $domain): DateTimeImmutable
    {
        $timeout = max(1, (int) config('tenant.domains.operational_connect_timeout_seconds', 5));
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $domain,
                'SNI_enabled' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            'ssl://'.$domain.':443',
            $errorCode,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );
        if (! is_resource($socket)) {
            throw new RuntimeException('TLS connection could not be established.');
        }

        try {
            $parameters = stream_context_get_params($socket);
            $certificate = $parameters['options']['ssl']['peer_certificate'] ?? null;
            if ($certificate === null) {
                throw new RuntimeException('TLS peer certificate was not available.');
            }

            $details = openssl_x509_parse($certificate);
            $expiresAt = is_array($details) ? ($details['validTo_time_t'] ?? null) : null;
            if (! is_int($expiresAt) && ! ctype_digit((string) $expiresAt)) {
                throw new RuntimeException('TLS certificate expiry could not be read.');
            }

            return (new DateTimeImmutable())->setTimestamp((int) $expiresAt);
        } finally {
            fclose($socket);
        }
    }
}
