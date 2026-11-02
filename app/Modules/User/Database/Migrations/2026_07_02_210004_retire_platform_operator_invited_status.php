<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INVITED_STATUS = 'invited';
    private const INACTIVE_STATUS = 'inactive';

    public function up(): void
    {
        DB::table('platform_operators')
            ->where('status', self::INVITED_STATUS)
            ->update([
                'status' => self::INACTIVE_STATUS,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('platform_operators')
            ->where('status', self::INACTIVE_STATUS)
            ->whereNull('credentials_ready_at')
            ->update([
                'status' => self::INVITED_STATUS,
                'updated_at' => now(),
            ]);
    }
};
