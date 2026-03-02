<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('fiscal_period_id');
            $table->string('reference_number');
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->string('status')->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
