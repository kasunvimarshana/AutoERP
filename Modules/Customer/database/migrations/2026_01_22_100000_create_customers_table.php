<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique()->comment('Unique customer identifier');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable()->default('USA');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            $table->string('company_name')->nullable();
            $table->string('tax_id')->nullable()->comment('Tax ID or Business Registration Number');
            $table->boolean('receive_notifications')->default(true);
            $table->boolean('receive_marketing')->default(false);
            $table->timestamp('last_service_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('customer_number');
            $table->index('email');
            $table->index('phone');
            $table->index('mobile');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
