<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_print_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('barcode_definition_id')->index();
            $table->unsignedBigInteger('label_template_id')->nullable()->index();
            $table->string('status', 20)->default('pending'); // pending|processing|completed|failed|cancelled
            $table->string('printer_target', 255)->nullable();  // IP:port or queue name
            $table->unsignedSmallInteger('copies')->default(1);
            $table->longText('rendered_output')->nullable();
            $table->json('variables')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'barcode_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_print_jobs');
    }
};
