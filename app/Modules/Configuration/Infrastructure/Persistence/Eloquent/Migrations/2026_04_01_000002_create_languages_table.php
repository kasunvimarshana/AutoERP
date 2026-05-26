<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');
            $table->string('code')->unique('languages_code_uk');
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable()->index('languages_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('languages_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('languages_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
