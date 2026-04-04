<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gs1_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('barcode_id');
            $table->foreign('barcode_id')->references('id')->on('gs1_barcodes');
            $table->string('label_format', 50);
            $table->text('content');
            $table->bigInteger('batch_id')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gs1_labels');
    }
};
