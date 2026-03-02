<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_posting_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('event_type');
            $table->unsignedBigInteger('debit_account_id');
            $table->unsignedBigInteger('credit_account_id');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('debit_account_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
            $table->foreign('credit_account_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_posting_rules');
    }
};
