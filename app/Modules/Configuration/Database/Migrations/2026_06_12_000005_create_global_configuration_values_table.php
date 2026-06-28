<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_configuration_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->string('key', 191)->unique('global_configuration_values_key_uk');
            $table->unsignedInteger('definition_version');
            $table->longText('value')->nullable();
            $table->string('value_type', 40);
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_configuration_values');
    }
};
