<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('category_id')->nullable()->index();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->uuid('reporter_id')->index();
            $table->uuid('assigned_to')->nullable()->index();
            $table->uuid('resolver_id')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('new');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_tickets');
    }
};
