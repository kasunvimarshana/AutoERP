<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('organization_unit_closures', function (Blueprint $table) {
            $table->unsignedBigInteger('ancestor_id')->index();
            $table->unsignedBigInteger('descendant_id')->index();
            $table->unsignedInteger('depth')->default(0);
            $table->primary(['ancestor_id', 'descendant_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('organization_unit_closures'); }
};
