<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_id'], 'document_events_tenant_document_index');
            $table->index(['document_id', 'event_type'], 'document_events_document_type_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX document_events_payload_gin ON document_events USING GIN (payload)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_events');
    }
};
