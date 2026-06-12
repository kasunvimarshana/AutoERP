<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_group_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_group_id')->constrained('tax_groups', 'id')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('taxes', 'id')->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tax_group_id', 'sequence'], 'tax_group_lines_group_sequence_uk');
            $table->unique(['tax_group_id', 'tax_id'], 'tax_group_lines_group_tax_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_lines');
    }
};
