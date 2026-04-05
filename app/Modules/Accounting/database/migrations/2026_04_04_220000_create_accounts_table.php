<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->string('code'); $table->string('name'); $table->string('type');
            $table->string('subtype')->nullable(); $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('balance',15,4)->default(0); $table->string('currency',3)->default('USD');
            $table->boolean('is_active')->default(true); $table->text('description')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->unique(['tenant_id','code']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('accounts'); }
};
