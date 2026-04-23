<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table): void {
            $table->string('label', 255)->nullable()->after('value');
            $table->string('color_code', 20)->nullable()->after('label');
            $table->boolean('is_active')->default(true)->after('color_code');
            $table->json('metadata')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table): void {
            $table->dropColumn(['label', 'color_code', 'is_active', 'metadata']);
        });
    }
};
