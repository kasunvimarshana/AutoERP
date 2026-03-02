<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            // Discriminates between an external URL and a platform-stored upload.
            // Defaults to 'url' to preserve backward compatibility with existing records.
            $table->string('image_source_type', 20)
                ->default('url')
                ->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            $table->dropColumn('image_source_type');
        });
    }
};
