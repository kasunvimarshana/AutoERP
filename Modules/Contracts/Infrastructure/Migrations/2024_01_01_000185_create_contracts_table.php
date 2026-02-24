<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('other');
            $table->string('party_name');
            $table->string('party_email')->nullable();
            $table->string('party_reference')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_value', 18, 8)->default('0.00000000');
            $table->char('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
