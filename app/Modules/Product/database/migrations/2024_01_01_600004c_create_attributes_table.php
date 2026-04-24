<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'attributes_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('attribute_groups', 'id', 'attributes_group_id_fk')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['text', 'select', 'number', 'boolean'])->default('select');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
