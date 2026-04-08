<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transaction_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->cascadeOnDelete();
            $table->string('matchable_type');
            $table->unsignedBigInteger('matchable_id');
            $table->decimal('match_amount', 19, 4);
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->timestamp('matched_at');
            $table->json('metadata')->nullable();
            $table->index(['bank_transaction_id', 'matchable_type', 'matchable_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_matches');
    }
};
