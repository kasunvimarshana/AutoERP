<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('org_unit_closures', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('org_units')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('org_units')->cascadeOnDelete();
            $table->integer('depth')->default(0);
            $table->primary(['ancestor_id', 'descendant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_closures');
    }
};
