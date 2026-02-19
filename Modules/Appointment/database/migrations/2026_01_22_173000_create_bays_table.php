<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('bay_number', 50);
            $table->enum('bay_type', ['standard', 'express', 'diagnostic', 'detailing', 'heavy_duty'])->default('standard');
            $table->enum('status', ['available', 'occupied', 'maintenance', 'inactive'])->default('available');
            $table->integer('capacity')->default(1)->comment('Number of vehicles that can fit');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'bay_number']);
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bays');
    }
};
