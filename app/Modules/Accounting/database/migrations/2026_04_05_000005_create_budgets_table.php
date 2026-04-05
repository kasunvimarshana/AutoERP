<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('expense_category_id')->nullable();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 15, 4);
            $table->decimal('spent_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
