<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 0002 — Audit Module
 *
 * Immutable append-only audit trail.
 * Every domain event that mutates state writes here.
 * Aligned to KVAutoERP's stated "all must be auditable" requirement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            // ── Polymorphic subject ────────────────────────────────────────
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');

            // ── Event ──────────────────────────────────────────────────────
            $table->string('event');
            // created|updated|deleted|restored|status_changed
            // stock_in|stock_out|transfer|adjustment|allocated|reserved
            // batch_created|lot_created|serial_assigned|valuation_changed

            // ── Actor ──────────────────────────────────────────────────────
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();   // user|system|api|webhook
            $table->string('user_name')->nullable();   // denormalized for display

            // ── Before / After snapshots ───────────────────────────────────
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable(); // array of changed keys

            // ── Request context ────────────────────────────────────────────
            $table->json('metadata')->nullable();
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id')->nullable();
            $table->string('session_id')->nullable();

            // ── Module / Domain tagging ────────────────────────────────────
            $table->string('module')->nullable();      // Inventory|Product|Batch|…
            $table->string('tags')->nullable();        // comma-separated

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'event']);
            $table->index(['tenant_id', 'module']);
            $table->index('occurred_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
