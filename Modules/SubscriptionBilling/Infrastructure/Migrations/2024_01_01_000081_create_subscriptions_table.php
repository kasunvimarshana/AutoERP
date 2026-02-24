<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('plan_id')->index();
            $table->string('subscriber_type', 64);
            $table->uuid('subscriber_id');
            $table->string('status', 16)->default('active');
            $table->decimal('amount', 18, 8)->default(0);
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['subscriber_type', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
