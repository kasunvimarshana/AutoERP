<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name')->comment('Net 30", "2/10 Net 30');
            $table->text('description')->nullable();
            $table->unsignedInteger('due_days')->default(30)->comment('days until full payment due');
            $table->unsignedInteger('discount_days')->nullable()->comment('days window for early payment discount');
            $table->decimal('discount_rate', 20, 4)->nullable()->comment('e.g., 2.00 for 2%');
            $table->string('payment_type')->default('net')->comment('net, end_of_month, end_of_next_month');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'payment_terms_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
