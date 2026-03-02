<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('account_type_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('restrict');
            $table->foreign('parent_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
