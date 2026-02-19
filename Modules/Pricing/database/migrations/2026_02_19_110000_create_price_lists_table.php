<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'scheduled', 'expired'])->default('active');
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('location_code', 50)->nullable();
            $table->string('customer_group', 100)->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
            $table->index(['status', 'is_default']);
            $table->index('customer_id');
            $table->index('location_code');
            $table->index('customer_group');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
