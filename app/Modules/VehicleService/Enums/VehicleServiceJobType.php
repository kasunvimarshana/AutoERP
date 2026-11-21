<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceJobType: string
{
    case FullService = 'full_service';
    case BodyWash = 'body_wash';
    case OilChange = 'oil_change';
    case WheelAlignment = 'wheel_alignment';
    case Accessories = 'accessories';

    public function tracksMileage(): bool
    {
        return in_array($this, [self::FullService, self::OilChange, self::WheelAlignment], true);
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     tracks_mileage: bool,
     *     is_active: bool,
     *     is_system: bool,
     *     sort_order: int
     * }>
     */
    public static function defaults(): array
    {
        return [
            [
                'code' => self::FullService->value,
                'name' => 'Full Service',
                'description' => 'Comprehensive vehicle maintenance service.',
                'tracks_mileage' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 10,
            ],
            [
                'code' => self::BodyWash->value,
                'name' => 'Body Wash',
                'description' => 'Exterior vehicle washing and cleaning.',
                'tracks_mileage' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 20,
            ],
            [
                'code' => self::OilChange->value,
                'name' => 'Oil Change',
                'description' => 'Engine oil and filter replacement.',
                'tracks_mileage' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 30,
            ],
            [
                'code' => self::WheelAlignment->value,
                'name' => 'Wheel Alignment',
                'description' => 'Wheel balancing and tire alignment service.',
                'tracks_mileage' => true,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 40,
            ],
            [
                'code' => self::Accessories->value,
                'name' => 'Accessories',
                'description' => 'Vehicle accessories installation and service.',
                'tracks_mileage' => false,
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 50,
            ],
        ];
    }
}
