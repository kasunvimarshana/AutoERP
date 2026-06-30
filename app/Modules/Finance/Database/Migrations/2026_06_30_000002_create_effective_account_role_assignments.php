<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const ROLES = 'finance_account_roles';
    private const ASSIGNMENTS = 'finance_account_assignments';
    private const RULES = 'finance_posting_profile_rules';
    private const NEW_RULES = 'finance_posting_profile_role_rules';
    private const LEGACY_RULES = 'finance_posting_profile_account_rules';
    private const OPENING_EFFECTIVE_DATE = '1900-01-01';

    public function up(): void
    {
        Schema::create(self::ROLES, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'finance_account_roles_tenant_code_uk');
            $table->foreign('tenant_id', 'finance_account_roles_tenant_fk')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create(self::ASSIGNMENTS, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('account_role_id');
            $table->unsignedBigInteger('account_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'organization_unit_id', 'account_role_id', 'effective_from', 'effective_to'],
                'finance_account_assignments_effective_ix',
            );
            $table->foreign('tenant_id', 'finance_account_assignments_tenant_fk')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('organization_unit_id', 'finance_account_assignments_org_fk')->references('id')->on('organization_units')->restrictOnDelete();
            $table->foreign('account_role_id', 'finance_account_assignments_role_fk')->references('id')->on(self::ROLES)->restrictOnDelete();
            $table->foreign('account_id', 'finance_account_assignments_account_fk')->references('id')->on('finance_accounts')->restrictOnDelete();
            $table->foreign('created_by', 'finance_account_assignments_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ended_by', 'finance_account_assignments_ended_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create(self::NEW_RULES, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('posting_profile_id');
            $table->string('line_key', 100);
            $table->unsignedBigInteger('account_role_id');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['posting_profile_id', 'line_key'], 'finance_posting_profile_rules_profile_line_uk');
            $table->foreign('tenant_id', 'finance_posting_profile_rules_tenant_fk')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('posting_profile_id', 'finance_posting_profile_rules_profile_fk')->references('id')->on('finance_posting_profiles')->cascadeOnDelete();
            $table->foreign('account_role_id', 'finance_posting_profile_rules_role_fk')->references('id')->on(self::ROLES)->restrictOnDelete();
        });

        $rules = DB::table(self::RULES.' as rule')
            ->join('finance_posting_profiles as profile', 'profile.id', '=', 'rule.posting_profile_id')
            ->select([
                'rule.id',
                'rule.tenant_id',
                'rule.posting_profile_id',
                'rule.line_key',
                'rule.account_id',
                'rule.description',
                'rule.created_at',
                'rule.updated_at',
                'profile.organization_unit_id',
            ])
            ->orderBy('rule.id')
            ->get();

        foreach ($rules as $rule) {
            $roleId = $this->roleId((int) $rule->tenant_id, (string) $rule->line_key);
            $this->migrateAssignment(
                (int) $rule->tenant_id,
                $rule->organization_unit_id !== null ? (int) $rule->organization_unit_id : null,
                $roleId,
                (int) $rule->account_id,
            );

            DB::table(self::NEW_RULES)->insert([
                'id' => (int) $rule->id,
                'tenant_id' => (int) $rule->tenant_id,
                'posting_profile_id' => (int) $rule->posting_profile_id,
                'line_key' => (string) $rule->line_key,
                'account_role_id' => $roleId,
                'description' => $rule->description,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ]);
        }

        Schema::drop(self::RULES);
        Schema::rename(self::NEW_RULES, self::RULES);
    }

    public function down(): void
    {
        Schema::create(self::LEGACY_RULES, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('posting_profile_id');
            $table->string('line_key', 100);
            $table->unsignedBigInteger('account_id');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['posting_profile_id', 'line_key'], 'finance_posting_profile_rules_profile_line_uk');
            $table->foreign('tenant_id', 'finance_posting_profile_rules_tenant_fk')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('posting_profile_id', 'finance_posting_profile_rules_profile_fk')->references('id')->on('finance_posting_profiles')->cascadeOnDelete();
            $table->foreign('account_id', 'finance_posting_profile_rules_account_fk')->references('id')->on('finance_accounts')->restrictOnDelete();
        });

        $rules = DB::table(self::RULES.' as rule')
            ->join('finance_posting_profiles as profile', 'profile.id', '=', 'rule.posting_profile_id')
            ->select([
                'rule.id',
                'rule.tenant_id',
                'rule.posting_profile_id',
                'rule.line_key',
                'rule.account_role_id',
                'rule.description',
                'rule.created_at',
                'rule.updated_at',
                'profile.organization_unit_id',
            ])
            ->orderBy('rule.id')
            ->get();

        foreach ($rules as $rule) {
            $assignment = DB::table(self::ASSIGNMENTS)
                ->where('tenant_id', (int) $rule->tenant_id)
                ->where('account_role_id', (int) $rule->account_role_id)
                ->when(
                    $rule->organization_unit_id === null,
                    fn ($query) => $query->whereNull('organization_unit_id'),
                    fn ($query) => $query->where('organization_unit_id', (int) $rule->organization_unit_id),
                )
                ->orderBy('effective_from')
                ->orderBy('id')
                ->first();
            if ($assignment === null) {
                throw new RuntimeException('Cannot restore direct posting profile rule because no account assignment exists.');
            }

            DB::table(self::LEGACY_RULES)->insert([
                'id' => (int) $rule->id,
                'tenant_id' => (int) $rule->tenant_id,
                'posting_profile_id' => (int) $rule->posting_profile_id,
                'line_key' => (string) $rule->line_key,
                'account_id' => (int) $assignment->account_id,
                'description' => $rule->description,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ]);
        }

        Schema::drop(self::RULES);
        Schema::rename(self::LEGACY_RULES, self::RULES);
        Schema::drop(self::ASSIGNMENTS);
        Schema::drop(self::ROLES);
    }

    private function roleId(int $tenantId, string $lineKey): int
    {
        $code = mb_strtolower(trim($lineKey));
        $existing = DB::table(self::ROLES)
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) DB::table(self::ROLES)->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'description' => 'Migrated semantic Finance account role.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrateAssignment(
        int $tenantId,
        ?int $organizationUnitId,
        int $roleId,
        int $accountId,
    ): void {
        $existing = DB::table(self::ASSIGNMENTS)
            ->where('tenant_id', $tenantId)
            ->where('account_role_id', $roleId)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->first();

        if ($existing !== null) {
            if ((int) $existing->account_id !== $accountId) {
                throw new RuntimeException('Conflicting direct posting mappings exist for the same Finance account role and scope.');
            }

            return;
        }

        DB::table(self::ASSIGNMENTS)->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $roleId,
            'account_id' => $accountId,
            'effective_from' => self::OPENING_EFFECTIVE_DATE,
            'effective_to' => null,
            'is_active' => true,
            'created_by' => null,
            'ended_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
