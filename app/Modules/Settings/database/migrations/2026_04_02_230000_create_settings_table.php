<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('group_key', 100);
            $table->string('setting_key', 100);
            $table->string('setting_type', 50)->default('string');
            $table->text('value')->nullable();
            $table->text('default_value')->nullable();
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'group_key', 'setting_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
