<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Resources;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TimezoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $zone = new DateTimeZone((string) $this->resource->name);
        $offset = $zone->getOffset(new DateTimeImmutable('now', $zone));
        $sign = $offset < 0 ? '-' : '+';
        $offset = abs($offset);

        return [
            'id' => (int) $this->resource->getKey(),
            'name' => (string) $this->resource->name,
            'display_name' => (string) $this->resource->display_name,
            'current_utc_offset' => sprintf(
                '%s%02d:%02d',
                $sign,
                intdiv($offset, 3600),
                intdiv($offset % 3600, 60),
            ),
            'is_active' => (bool) $this->resource->is_active,
            'row_version' => (int) $this->resource->row_version,
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
