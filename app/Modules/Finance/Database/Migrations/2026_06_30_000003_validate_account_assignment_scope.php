<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invalid = DB::table('finance_account_assignments as assignment')
            ->join('finance_accounts as account', 'account.id', '=', 'assignment.account_id')
            ->whereColumn('assignment.tenant_id', '<>', 'account.tenant_id')
            ->orWhere(function ($query): void {
                $query->whereNull('assignment.organization_unit_id')
                    ->whereNotNull('account.organization_unit_id');
            })
            ->orWhere(function ($query): void {
                $query->whereNotNull('assignment.organization_unit_id')
                    ->where(function ($scope): void {
                        $scope->whereNull('account.organization_unit_id')
                            ->orWhereColumn('assignment.organization_unit_id', '<>', 'account.organization_unit_id');
                    });
            })
            ->exists();

        if ($invalid) {
            throw new RuntimeException('Finance account assignment scope does not match its assigned account scope.');
        }
    }

    public function down(): void
    {
        // Validation-only migration; canonical schema is unchanged.
    }
};
