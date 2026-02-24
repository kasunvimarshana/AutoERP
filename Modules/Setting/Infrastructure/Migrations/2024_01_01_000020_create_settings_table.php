<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('group');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->json('validation_rules')->nullable();
            $table->boolean('is_global')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};
