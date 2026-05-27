<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('item_type');
            $table->string('description')->nullable();
            $table->decimal('line_total', 20, 4)->default(0);
            $table->unsignedInteger('sequence')->default(1);
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'sequence'], 'document_items_document_sequence_index');
            $table->index(['item_type'], 'document_items_type_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX document_items_data_gin ON document_items USING GIN (data)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
