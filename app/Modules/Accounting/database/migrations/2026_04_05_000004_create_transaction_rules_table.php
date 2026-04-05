<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->json('conditions');
            $table->json('actions');
            $table->string('apply_to')->default('all'); // all|debit|credit
            $table->unsignedInteger('match_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_rules');
    }
};
