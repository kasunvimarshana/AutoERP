<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_barcode_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('width')->default(50);
            $table->integer('height')->default(30);
            $table->integer('top_margin')->default(10);
            $table->integer('left_margin')->default(10);
            $table->integer('row_distance')->default(5);
            $table->integer('col_distance')->default(5);
            $table->integer('stickers_in_one_row')->default(2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_continuous')->default(false);
            $table->json('paper_size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_barcode_config');
    }
};
