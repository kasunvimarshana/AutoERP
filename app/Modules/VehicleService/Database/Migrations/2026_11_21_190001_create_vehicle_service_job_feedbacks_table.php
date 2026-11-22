<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_feedbacks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id');
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->foreignId('user_id')->nullable();
            $table->string('author_name', 150)->nullable();
            $table->string('feedback_type', 30)->default('review');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('title', 255)->nullable();
            $table->text('comment');
            $table->boolean('is_customer_visible')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'vehicle_service_job_feedbacks_id_tenant_uk');
            $table->index(['tenant_id', 'vehicle_service_job_id', 'created_at'], 'vehicle_service_job_feedbacks_job_created_ix');
            $table->index(['tenant_id', 'feedback_type', 'rating'], 'vehicle_service_job_feedbacks_type_rating_ix');

            $table->foreign('tenant_id', 'vehicle_service_job_feedbacks_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_job_feedbacks_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();

            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_job_feedbacks_job_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->cascadeOnDelete();

            $table->foreign(['user_id', 'tenant_id'], 'vehicle_service_job_feedbacks_user_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'vehicle_service_job_feedbacks_created_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['updated_by', 'tenant_id'], 'vehicle_service_job_feedbacks_updated_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_feedbacks');
    }
};
