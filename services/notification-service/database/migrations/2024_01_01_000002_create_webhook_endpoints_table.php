<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36);
            $table->string('name', 255);
            $table->string('url');
            $table->string('secret', 255);
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('headers')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id'); $table->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('webhook_endpoints'); }
};
