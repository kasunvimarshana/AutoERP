<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenant_onboarding_steps')
            ->where('step', 'initial_admin_invitation')
            ->update([
                'step' => 'initial_admin_account',
                'owner_module' => 'User',
            ]);
    }

    public function down(): void
    {
        DB::table('tenant_onboarding_steps')
            ->where('step', 'initial_admin_account')
            ->update([
                'step' => 'initial_admin_invitation',
                'owner_module' => 'Auth',
            ]);
    }
};
