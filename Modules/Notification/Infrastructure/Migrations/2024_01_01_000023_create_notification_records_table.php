<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('user_id')->index();
            $table->string('type');
            $table->string('channel');
            $table->json('data')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('notification_records'); }
};
