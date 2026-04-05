<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('notification_type', 100);
            $table->string('channel', 30);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // One preference row per user per type+channel (composite with tenant for multi-tenancy)
            $table->unique(['tenant_id', 'user_id', 'notification_type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
