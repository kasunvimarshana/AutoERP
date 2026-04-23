<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combo_items', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('metadata');
            $table->boolean('is_optional')->default(false)->after('sort_order');
            $table->text('notes')->nullable()->after('is_optional');
        });
    }

    public function down(): void
    {
        Schema::table('combo_items', function (Blueprint $table): void {
            $table->dropColumn(['sort_order', 'is_optional', 'notes']);
        });
    }
};
