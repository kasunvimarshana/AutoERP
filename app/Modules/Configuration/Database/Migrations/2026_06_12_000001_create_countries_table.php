<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');
            $table->string('code')->unique('countries_code_uk');
            $table->string('name');
            $table->string('phone_code')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('countries_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('countries_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('countries_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
