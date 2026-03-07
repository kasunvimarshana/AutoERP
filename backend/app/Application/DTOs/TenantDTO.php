<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for Tenant create / update operations.
 */
final class TenantDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $domain = null,
        public readonly string $plan = 'starter',
        public readonly string $status = 'active',
        public readonly ?array $config = null,
        public readonly int $maxUsers = 10,
        public readonly string $timezone = 'UTC',
        public readonly string $locale = 'en',
        public readonly string $currency = 'USD',
        public readonly ?string $logoUrl = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:      $request->string('name')->toString(),
            slug:      $request->string('slug')->slug()->toString(),
            domain:    $request->input('domain'),
            plan:      $request->input('plan', 'starter'),
            status:    $request->input('status', 'active'),
            config:    $request->input('config'),
            maxUsers:  (int) $request->input('max_users', 10),
            timezone:  $request->input('timezone', 'UTC'),
            locale:    $request->input('locale', 'en'),
            currency:  $request->input('currency', 'USD'),
            logoUrl:   $request->input('logo_url'),
            metadata:  $request->input('metadata'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'      => $this->name,
            'slug'      => $this->slug,
            'domain'    => $this->domain,
            'plan'      => $this->plan,
            'status'    => $this->status,
            'config'    => $this->config,
            'max_users' => $this->maxUsers,
            'timezone'  => $this->timezone,
            'locale'    => $this->locale,
            'currency'  => $this->currency,
            'logo_url'  => $this->logoUrl,
            'metadata'  => $this->metadata,
        ], fn ($v) => $v !== null);
    }
}
