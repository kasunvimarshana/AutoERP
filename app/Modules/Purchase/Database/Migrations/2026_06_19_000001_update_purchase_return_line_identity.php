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
        Schema::table('purchase_return_lines', function (Blueprint $table): void {
            $table->unsignedInteger('line_number')->nullable()->after('purchase_return_id');
            $table->string('client_line_key', 100)->nullable()->after('line_number');
        });

        $returnIds = DB::table('purchase_return_lines')
            ->select('purchase_return_id')
            ->distinct()
            ->orderBy('purchase_return_id')
            ->pluck('purchase_return_id');

        foreach ($returnIds as $returnId) {
            $lineIds = DB::table('purchase_return_lines')
                ->where('purchase_return_id', $returnId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($lineIds as $index => $lineId) {
                DB::table('purchase_return_lines')
                    ->where('id', $lineId)
                    ->update(['line_number' => $index + 1]);
            }
        }

        Schema::table('purchase_return_lines', function (Blueprint $table): void {
            $table->string('source_line_type')->nullable()->change();
            $table->unsignedBigInteger('source_line_id')->nullable()->change();
            $table->unique(['purchase_return_id', 'line_number'], 'purchase_return_lines_return_line_number_uk');
            $table->unique(['purchase_return_id', 'client_line_key'], 'purchase_return_lines_return_client_key_uk');
            $table->unique(['purchase_return_id', 'source_line_type', 'source_line_id'], 'purchase_return_lines_return_source_uk');
        });
    }

    public function down(): void
    {
        DB::table('purchase_return_lines')
            ->whereNull('source_line_type')
            ->update(['source_line_type' => 'manual_supplier_return']);

        DB::table('purchase_return_lines')
            ->whereNull('source_line_id')
            ->update(['source_line_id' => 0]);

        Schema::table('purchase_return_lines', function (Blueprint $table): void {
            $table->dropUnique('purchase_return_lines_return_line_number_uk');
            $table->dropUnique('purchase_return_lines_return_client_key_uk');
            $table->dropUnique('purchase_return_lines_return_source_uk');
            $table->string('source_line_type')->nullable(false)->change();
            $table->unsignedBigInteger('source_line_id')->nullable(false)->change();
            $table->dropColumn(['line_number', 'client_line_key']);
        });
    }
};
