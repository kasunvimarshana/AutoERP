<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('field_key', 120);
            $table->string('value_type', 40);
            $table->string('value_string')->nullable();
            $table->decimal('value_number', 20, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->text('value_text')->nullable();
            $table->string('value_reference_type', 120)->nullable();
            $table->unsignedBigInteger('value_reference_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'document_id', 'field_key'], 'document_field_values_unique');
            $table->index(['tenant_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_field_values');
    }
};
