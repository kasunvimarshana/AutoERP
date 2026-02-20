<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('idempotency_key', 255);
            $table->string('request_method', 10);
            $table->string('request_path', 500);
            $table->smallInteger('response_status')->nullable();
            $table->mediumText('response_body')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // One key per user (or IP) per method+path combination
            $table->unique(['user_id', 'idempotency_key'], 'uidx_user_idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
