<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('type', 50)->default('organisation');
            $table->string('name', 255);
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('parent_id')->references('id')->on('organisations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisations');
    }
};
