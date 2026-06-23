<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use ErrorException;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Throwable;

final class DnsTenantDomainOwnershipVerifier implements TenantDomainOwnershipVerifierInterface
{
    public function verify(string $domain, string $expectedTokenHash): TenantDomainVerificationResult
    {
        $prefix = (string) config('tenant.domains.verification_txt_prefix', '_autoerp-verification');
        $valuePrefix = (string) config('tenant.domains.verification_value_prefix', 'autoerp-verification=');
        $host = $prefix.'.'.$domain;

        try {
            set_error_handler(static function (int $severity, string $message): never {
                throw new ErrorException($message, 0, $severity);
            });
            $records = dns_get_record($host, DNS_TXT);
        } catch (Throwable $exception) {
            return TenantDomainVerificationResult::failed(
                'dns_lookup_failed',
                'DNS verification could not be completed: '.$exception->getMessage(),
            );
        } finally {
            restore_error_handler();
        }

        if (! is_array($records) || $records === []) {
            return TenantDomainVerificationResult::failed(
                'txt_record_missing',
                'The expected DNS TXT record was not found.',
            );
        }

        foreach ($records as $record) {
            $txt = is_array($record) && isset($record['txt']) ? trim((string) $record['txt']) : '';
            if (! str_starts_with($txt, $valuePrefix)) {
                continue;
            }

            $token = substr($txt, strlen($valuePrefix));
            if ($token !== '' && hash_equals($expectedTokenHash, hash('sha256', $token))) {
                return TenantDomainVerificationResult::verified();
            }
        }

        return TenantDomainVerificationResult::failed(
            'txt_record_mismatch',
            'The DNS TXT record does not contain the current verification value.',
        );
    }
}
