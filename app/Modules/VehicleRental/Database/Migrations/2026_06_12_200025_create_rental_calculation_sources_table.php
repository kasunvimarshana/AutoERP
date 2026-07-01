<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    private const SOURCE_SHAPE_CONSTRAINT = 'rental_calculation_sources_valid_source_ck';

    public function up(): void
    {
        Schema::create('rental_calculation_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_calculation_sources_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('calculation_run_id');
            $table->enum('source_kind', ['usage_context', 'expense_allocation']);
            $table->foreignId('usage_context_id')->nullable();
            $table->foreignId('expense_allocation_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['calculation_run_id', 'usage_context_id'],
                'rental_calculation_sources_run_context_uk',
            );
            $table->unique(
                ['calculation_run_id', 'expense_allocation_id'],
                'rental_calculation_sources_run_expense_uk',
            );
            $table->index(
                ['usage_context_id', 'status'],
                'rental_calculation_sources_context_status_ix',
            );
            $table->index(
                ['expense_allocation_id', 'status'],
                'rental_calculation_sources_expense_status_ix',
            );
            $table->unique(['id', 'tenant_id'], 'rental_calculation_sources_id_tenant_uk');

            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'rental_calculation_sources_org_unit_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(
                ['calculation_run_id', 'tenant_id'],
                'rental_calculation_sources_run_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('rental_calculation_runs')
                ->restrictOnDelete();
            $table->foreign(
                ['usage_context_id', 'tenant_id'],
                'rental_calculation_sources_context_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('rental_usage_contexts')
                ->restrictOnDelete();
            $table->foreign(
                ['expense_allocation_id', 'tenant_id'],
                'rental_calculation_sources_expense_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('rental_expense_allocations')
                ->restrictOnDelete();
            $table->foreign(
                ['created_by', 'tenant_id'],
                'rental_calculation_sources_created_by_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(
                ['updated_by', 'tenant_id'],
                'rental_calculation_sources_updated_by_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });

        $this->addSourceShapeConstraint();
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_calculation_sources');
    }

    private function addSourceShapeConstraint(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlsrv'], true)) {
            DB::statement(sprintf(
                'ALTER TABLE rental_calculation_sources ADD CONSTRAINT %s CHECK (%s)',
                self::SOURCE_SHAPE_CONSTRAINT,
                $this->sourceShapePredicate(),
            ));

            return;
        }

        if ($driver === 'sqlite') {
            $this->addSqliteSourceShapeTrigger('insert', 'INSERT');
            $this->addSqliteSourceShapeTrigger('update', 'UPDATE');

            return;
        }

        throw new RuntimeException("Unsupported database driver for rental calculation source integrity: {$driver}");
    }

    private function addSqliteSourceShapeTrigger(string $suffix, string $operation): void
    {
        $predicate = str_replace(
            ['source_kind', 'usage_context_id', 'expense_allocation_id'],
            ['NEW.source_kind', 'NEW.usage_context_id', 'NEW.expense_allocation_id'],
            $this->sourceShapePredicate(),
        );

        DB::unprepared(sprintf(
            <<<'SQL'
                CREATE TRIGGER rental_calculation_sources_shape_%s
                BEFORE %s ON rental_calculation_sources
                FOR EACH ROW
                WHEN NOT (%s)
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid rental calculation source shape.');
                END
            SQL,
            $suffix,
            $operation,
            $predicate,
        ));
    }

    private function sourceShapePredicate(): string
    {
        return <<<'SQL'
            (
                source_kind = 'usage_context'
                AND usage_context_id IS NOT NULL
                AND expense_allocation_id IS NULL
            )
            OR
            (
                source_kind = 'expense_allocation'
                AND usage_context_id IS NULL
                AND expense_allocation_id IS NOT NULL
            )
        SQL;
    }
};
