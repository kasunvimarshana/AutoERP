<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_manifests', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('alias')->unique();
            $table->text('description')->nullable();
            $table->string('version');
            $table->json('keywords')->nullable();
            $table->json('requires')->nullable()->comment('Array of dependency module aliases');
            $table->boolean('active')->default(true);
            $table->json('manifest_data')->nullable()->comment('Full raw manifest payload');
            $table->timestamps();
            $table->softDeletes();

            $table->index('alias');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_manifests');
    }
};
