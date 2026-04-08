<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_closure', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('organization_units')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('organization_units')->cascadeOnDelete();
            $table->unsignedInteger('depth');
            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_closure');
    }
};
