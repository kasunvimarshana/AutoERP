<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('event'); // created, updated, deleted, restored, login, etc.
            $table->morphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['tenant_id', 'auditable_type', 'auditable_id'], 'idx_audit_logs_tenant_morphable');
            $table->index(['tenant_id', 'occurred_at'], 'idx_audit_logs_tenant_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};