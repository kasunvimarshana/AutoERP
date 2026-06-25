<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_posting_profile_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('posting_profile_id')
                ->constrained('finance_posting_profiles', 'id')
                ->cascadeOnDelete();
            $table->string('line_key', 100);
            $table->foreignId('account_id')->constrained('finance_accounts', 'id');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(
                ['posting_profile_id', 'line_key'],
                'finance_posting_profile_rules_profile_key_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_posting_profile_rules');
    }
};
