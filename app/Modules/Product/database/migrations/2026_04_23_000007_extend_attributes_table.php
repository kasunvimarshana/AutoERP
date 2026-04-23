<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->string('code', 100)->nullable()->after('name');
            $table->text('description')->nullable()->after('code');
            $table->unsignedInteger('sort_order')->default(0)->after('description');
            $table->boolean('is_active')->default(true)->after('sort_order');
            $table->boolean('is_filterable')->default(false)->after('is_active');
            $table->json('metadata')->nullable()->after('is_filterable');
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->dropColumn(['code', 'description', 'sort_order', 'is_active', 'is_filterable', 'metadata']);
        });
    }
};
