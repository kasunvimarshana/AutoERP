<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->string('type', 30)->default('scheduled');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('amount', 15, 6);
            $table->decimal('book_value_before', 15, 6);
            $table->decimal('book_value_after', 15, 6);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->dateTime('depreciated_at');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['asset_id', 'period_year', 'period_month', 'type']);
            $table->index(['tenant_id', 'period_year', 'period_month']);
        });
    }
    public function down(): void { Schema::dropIfExists('asset_depreciations'); }
};
