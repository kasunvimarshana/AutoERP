<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->expandPaymentStatusEnum();
        $this->expandPaymentMethodTypeEnum();

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'source_type')) {
                $table->string('source_type', 150)->nullable()->after('party_id');
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->string('allocation_status', 50)->default('unapplied')->after('source_id');
                $table->json('metadata')->nullable()->after('notes');
                $table->index(['source_type', 'source_id'], 'payments_source_idx');
            }
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_methods', 'metadata')) {
                $table->json('metadata')->nullable()->after('sort_order');
            }
        });

        Schema::table('payment_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_lines', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
                $table->index(['payment_method_id', 'status'], 'payment_lines_method_status_idx');
            }
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_allocations', 'allocation_method')) {
                $table->string('allocation_method', 50)->default('specific_invoice')->after('allocation_date');
                $table->json('metadata')->nullable()->after('status');
            }
        });

        Schema::table('payment_unapplied_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_unapplied_balances', 'balance_type')) {
                $table->string('balance_type', 50)->default('credit')->after('payment_id');
                $table->string('party_type', 150)->nullable()->after('balance_type');
                $table->unsignedBigInteger('party_id')->nullable()->after('party_type');
                $table->string('source_type', 150)->nullable()->after('party_id');
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->string('allocation_status', 50)->default('unapplied')->after('source_id');
                $table->json('metadata')->nullable()->after('status');
                $table->index(['party_type', 'party_id', 'status'], 'payment_unapplied_party_status_idx');
                $table->index(['source_type', 'source_id'], 'payment_unapplied_source_idx');
            }
        });

        Schema::table('payment_refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_refunds', 'metadata')) {
                $table->json('metadata')->nullable()->after('status');
            }
        });

        Schema::table('payment_reversals', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_reversals', 'metadata')) {
                $table->json('metadata')->nullable()->after('status');
                $table->unique('payment_id', 'payment_reversals_payment_uk');
            }
        });

        Schema::create('payment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments', 'id')->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'changed_at'], 'payment_status_histories_payment_date_idx');
            $table->index(['tenant_id', 'organization_unit_id'], 'payment_status_histories_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_histories');

        Schema::table('payment_reversals', function (Blueprint $table) {
            if (Schema::hasColumn('payment_reversals', 'metadata')) {
                $table->dropUnique('payment_reversals_payment_uk');
                $table->dropColumn('metadata');
            }
        });

        Schema::table('payment_refunds', function (Blueprint $table) {
            if (Schema::hasColumn('payment_refunds', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });

        Schema::table('payment_unapplied_balances', function (Blueprint $table) {
            if (Schema::hasColumn('payment_unapplied_balances', 'balance_type')) {
                $table->dropIndex('payment_unapplied_party_status_idx');
                $table->dropIndex('payment_unapplied_source_idx');
                $table->dropColumn([
                    'balance_type',
                    'party_type',
                    'party_id',
                    'source_type',
                    'source_id',
                    'allocation_status',
                    'metadata',
                ]);
            }
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('payment_allocations', 'allocation_method')) {
                $table->dropColumn(['allocation_method', 'metadata']);
            }
        });

        Schema::table('payment_lines', function (Blueprint $table) {
            if (Schema::hasColumn('payment_lines', 'metadata')) {
                $table->dropIndex('payment_lines_method_status_idx');
                $table->dropColumn('metadata');
            }
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            if (Schema::hasColumn('payment_methods', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'source_type')) {
                $table->dropIndex('payments_source_idx');
                $table->dropColumn([
                    'source_type',
                    'source_id',
                    'allocation_status',
                    'metadata',
                ]);
            }
        });
    }

    private function expandPaymentStatusEnum(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE payments MODIFY status ENUM('draft','pending_approval','approved','posted','partially_allocated','fully_allocated','allocated','refunded','void','reversed','cancelled') NOT NULL DEFAULT 'draft'"
        );
    }

    private function expandPaymentMethodTypeEnum(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE payment_methods MODIFY method_type ENUM('cash','cheque','bank_transfer','card','credit_note','advance','deposit','wallet','custom','bank','online','transfer','mobile_wallet','debit_note','other') NOT NULL"
        );
    }
};
