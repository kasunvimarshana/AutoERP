<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->dropForeign('journal_entry_lines_cost_center_id_fk');
            $table->foreign('cost_center_id', 'journal_entry_lines_cost_center_id_fk')
                ->references('id')->on('cost_centers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->dropForeign('journal_entry_lines_cost_center_id_fk');
            $table->foreign('cost_center_id', 'journal_entry_lines_cost_center_id_fk')
                ->references('id')->on('org_units')->nullOnDelete();
        });
    }
};
