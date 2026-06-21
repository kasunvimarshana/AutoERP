<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timezones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->string('name', 100)
                ->unique('timezones_name_uk')
                ->comment('IANA timezone identifier');
            $table->string('display_name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(
                ['is_active', 'display_name'],
                'timezones_active_display_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timezones');
    }
};
