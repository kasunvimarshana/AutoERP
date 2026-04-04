<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('code', 50);
            $table->decimal('rate', 10, 4);
            $table->string('type', 20)->default('percentage');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('region', 100)->nullable();
            $table->string('tax_class', 50)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('tax_rates'); }
};
