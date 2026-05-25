<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Domain\Constants\ConfigurationSource;
use Modules\Configuration\Domain\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('value_type', 20)->default(ConfigurationValueType::NULL);
            $table->string('source', 20)->default(ConfigurationSource::DATABASE);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
