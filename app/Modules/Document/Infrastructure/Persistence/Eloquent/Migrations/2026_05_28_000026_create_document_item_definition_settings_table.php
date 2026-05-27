<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_item_definition_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_item_definition_id')
                ->constrained('document_item_definitions')
                ->cascadeOnDelete();
            $table->string('setting_group', 80)->default('general');
            $table->string('setting_key', 120);
            $table->string('value_type', 40);
            $table->string('value_string')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_decimal', 20, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->text('value_text')->nullable();
            $table->unsignedBigInteger('value_file_id')->nullable();
            $table->string('value_reference_type', 120)->nullable();
            $table->unsignedBigInteger('value_reference_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'document_item_definition_id', 'setting_group', 'setting_key'],
                'document_item_definition_settings_unique'
            );
            $table->index(
                ['tenant_id', 'document_item_definition_id', 'value_type'],
                'document_item_definition_settings_type_index'
            );
            $table->index(
                ['tenant_id', 'document_item_definition_id', 'created_at'],
                'document_item_definition_settings_created_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_item_definition_settings');
    }
};
