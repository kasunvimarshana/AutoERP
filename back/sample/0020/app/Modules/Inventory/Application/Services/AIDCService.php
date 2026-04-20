<?php

namespace App\Modules\Inventory\Application\Services;

use App\Modules\Core\Domain\Models\AIDCIdentifier;

class AIDCService {
    /**
     * Provides technology-agnostic lookup for Product, Batch, or Serial identifiers.
     */
    public function findByAny(string $code): ?object {
        $identifier = AIDCIdentifier::where('identifier_value', $code)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first();

        return $identifier?->linkable;
    }

    /**
     * Assigns an AIDC identifier (Barcode/RFID) to an entity.
     */
    public function assignIdentifier(string $code, object $entity): AIDCIdentifier {
        return AIDCIdentifier::create([
            'tenant_id' => auth()->user()->tenant_id,
            'identifier_value' => $code,
            'linkable_type' => get_class($entity),
            'linkable_id' => $entity->id
        ]);
    }
}
