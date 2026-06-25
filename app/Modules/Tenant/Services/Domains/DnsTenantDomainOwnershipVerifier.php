<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;

final class DnsTenantDomainOwnershipVerifier implements TenantDomainOwnershipVerifierInterface
{
    public function isVerified(string $domain, string $expectedTokenHash): bool
    {
        $prefix = (string) config('tenant.domains.verification_txt_prefix', '_autoerp-verification');
        $valuePrefix = (string) config('tenant.domains.verification_value_prefix', 'autoerp-verification=');
        $records = @dns_get_record($prefix.'.'.$domain, DNS_TXT);
        if (! is_array($records)) {
            return false;
        }
        foreach ($records as $record) {
            $txt = is_array($record) && isset($record['txt']) ? trim((string) $record['txt']) : '';
            if (! str_starts_with($txt, $valuePrefix)) {
                continue;
            }
            $token = substr($txt, strlen($valuePrefix));
            if ($token !== '' && hash_equals($expectedTokenHash, hash('sha256', $token))) {
                return true;
            }
        }
        return false;
    }
}
