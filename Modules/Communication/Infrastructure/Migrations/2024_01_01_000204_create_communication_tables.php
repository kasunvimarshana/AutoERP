<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('channel');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('communication_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('channel_id')->index();
            $table->uuid('sender_id')->index();
            $table->text('body');
            $table->string('type')->default('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_channels');
    }
};
