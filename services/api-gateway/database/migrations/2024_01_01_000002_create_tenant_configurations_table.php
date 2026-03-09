<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('config_key');
            $table->text('config_value')->nullable();
            $table->string('config_group', 100)->default('general');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'config_key']);
            $table->index('config_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configurations');
    }
};
