<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateDefaultSettings extends Migration
{
    public function up()
    {
        $settings = [
            [
                'key' => 'inventory.default_valuation_method',
                'group' => 'inventory',
                'value' => 'fifo',
                'type' => 'string',
                'description' => 'Default inventory valuation method (fifo, lifo, weighted_avg, specific)',
                'is_editable' => true,
            ],
            [
                'key' => 'inventory.default_rotation_strategy',
                'group' => 'inventory',
                'value' => 'fefo',
                'type' => 'string',
                'description' => 'Default stock rotation strategy (fefo, fifo, lifo, nearest_expiry)',
                'is_editable' => true,
            ],
            [
                'key' => 'inventory.default_allocation_algorithm',
                'group' => 'inventory',
                'value' => 'nearest_expiry',
                'type' => 'string',
                'description' => 'Default allocation algorithm for sales orders',
                'is_editable' => true,
            ],
            [
                'key' => 'inventory.reservation_expiry_days',
                'group' => 'inventory',
                'value' => '7',
                'type' => 'integer',
                'description' => 'Number of days inventory reservations are valid',
                'is_editable' => true,
            ],
            [
                'key' => 'inventory.enable_serial_tracking',
                'group' => 'inventory',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable serial number tracking',
                'is_editable' => true,
            ],
            [
                'key' => 'inventory.enable_batch_tracking',
                'group' => 'inventory',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable batch/lot tracking',
                'is_editable' => true,
            ],
            [
                'key' => 'returns.restocking_fee_percentage',
                'group' => 'returns',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Default restocking fee percentage',
                'is_editable' => true,
            ],
            [
                'key' => 'returns.require_quality_inspection',
                'group' => 'returns',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Require quality inspection for returns',
                'is_editable' => true,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down()
    {
        DB::table('settings')->truncate();
    }
}