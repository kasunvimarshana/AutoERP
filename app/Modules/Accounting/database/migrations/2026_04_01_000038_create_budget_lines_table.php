<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('budget_id')->index();
            $table->uuid('account_id')->index();
            $table->string('period');
            $table->json('amounts');
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }
    public function down(): void { Schema::dropIfExists('budget_lines'); }
};
