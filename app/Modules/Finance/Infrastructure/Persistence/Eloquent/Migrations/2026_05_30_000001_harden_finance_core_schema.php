<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounts', 'account_group')) {
                $table->string('account_group')->nullable()->after('type')->comment('Generic account grouping for reporting and UI trees');
            }
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('journal_entries', 'source_module')) {
                $table->string('source_module')->nullable()->after('reference_id')->comment('Generic source module key');
            }
            if (! Schema::hasColumn('journal_entries', 'source_type')) {
                $table->string('source_type')->nullable()->after('source_module')->comment('Generic source document/event type');
            }
            if (! Schema::hasColumn('journal_entries', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->comment('Generic source identifier');
            }
            if (! Schema::hasColumn('journal_entries', 'source_reference')) {
                $table->string('source_reference')->nullable()->after('source_id')->comment('Human-readable source number/reference');
            }
            if (! Schema::hasColumn('journal_entries', 'currency_id')) {
                $table->unsignedBigInteger('currency_id')->nullable()->after('status')->comment('Currency reference for journal header when applicable');
            }
            if (! Schema::hasColumn('journal_entries', 'total_debit')) {
                $table->decimal('total_debit', 20, 4)->default(0)->after('currency_id')->comment('Backend-calculated journal debit total');
            }
            if (! Schema::hasColumn('journal_entries', 'total_credit')) {
                $table->decimal('total_credit', 20, 4)->default(0)->after('total_debit')->comment('Backend-calculated journal credit total');
            }
            if (! Schema::hasColumn('journal_entries', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('posted_at');
            }

            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'journal_entries_source_idx');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('journal_entry_lines', 'party_type')) {
                $table->string('party_type')->nullable()->after('cost_center_id');
            }
            if (! Schema::hasColumn('journal_entry_lines', 'party_id')) {
                $table->unsignedBigInteger('party_id')->nullable()->after('party_type');
            }
            if (! Schema::hasColumn('journal_entry_lines', 'source_line_reference')) {
                $table->string('source_line_reference')->nullable()->after('line_number')->comment('Generic source line reference');
            }

            $table->index(['tenant_id', 'party_type', 'party_id'], 'journal_entry_lines_party_idx');
        });

        foreach (['ap_transactions', 'ar_transactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'source_module')) {
                    $table->string('source_module')->nullable()->after('reference_id');
                }
                if (! Schema::hasColumn($tableName, 'source_type')) {
                    $table->string('source_type')->nullable()->after('source_module');
                }
                if (! Schema::hasColumn($tableName, 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                }
                if (! Schema::hasColumn($tableName, 'source_reference')) {
                    $table->string('source_reference')->nullable()->after('source_id');
                }
                if (! Schema::hasColumn($tableName, 'original_amount')) {
                    $table->decimal('original_amount', 20, 4)->default(0)->after('credit_amount')->comment('Backend-maintained original transaction amount');
                }
                if (! Schema::hasColumn($tableName, 'paid_amount')) {
                    $table->decimal('paid_amount', 20, 4)->default(0)->after('original_amount')->comment('Backend-maintained paid/settled amount');
                }
                if (! Schema::hasColumn($tableName, 'outstanding_amount')) {
                    $table->decimal('outstanding_amount', 20, 4)->default(0)->after('paid_amount')->comment('Backend-maintained open balance');
                }
                if (! Schema::hasColumn($tableName, 'status')) {
                    $table->string('status')->default('OPEN')->after('due_date');
                }

                $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], $tableName.'_source_idx');
            });
        }

        Schema::table('bank_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('bank_transactions', 'source_module')) {
                $table->string('source_module')->nullable()->after('category_rule_id');
            }
            if (! Schema::hasColumn('bank_transactions', 'source_type')) {
                $table->string('source_type')->nullable()->after('source_module');
            }
            if (! Schema::hasColumn('bank_transactions', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('bank_transactions', 'source_reference')) {
                $table->string('source_reference')->nullable()->after('source_id');
            }

            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'bank_transactions_source_idx');
        });

        Schema::table('budget_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('budget_lines', 'used_amount')) {
                $table->decimal('used_amount', 20, 4)->default(0)->after('total_amount')->comment('Backend-calculated/read-model budget usage');
            }
            if (! Schema::hasColumn('budget_lines', 'variance_amount')) {
                $table->decimal('variance_amount', 20, 4)->default(0)->after('used_amount')->comment('Backend-calculated/read-model variance');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration hardens the Finance schema for forward compatibility.
    }
};
