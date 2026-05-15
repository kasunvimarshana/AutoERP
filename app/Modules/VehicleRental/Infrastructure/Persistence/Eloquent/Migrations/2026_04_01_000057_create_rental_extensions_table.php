<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->date('original_end_date');
            $table->date('new_end_date');
            $table->integer('extended_days');
            $table->decimal('additional_charge', 20, 4)->default(0);
            $table->decimal('revised_daily_rate', 20, 4)->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->default('approved');
            $table->unsignedBigInteger('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('rental_extensions'); }
};
