<?php
namespace Modules\Tenant\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Domain\ValueObjects\FeatureFlags;

class Tenant extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $email,
        public readonly string $status,
        public readonly ?DatabaseConfig $databaseConfig = null,
        public readonly ?FeatureFlags $featureFlags = null,
        public readonly ?string $plan = null,
        public readonly ?\DateTimeImmutable $trialEndsAt = null,
    ) {
        parent::__construct($id);
    }
}
