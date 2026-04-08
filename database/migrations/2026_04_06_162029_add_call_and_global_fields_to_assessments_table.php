<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('target_entity')->nullable()->comment('Programme name for PHI or Service name for ITSM');
            
            // Call context
            $table->date('call_date')->nullable();
            $table->string('call_type')->nullable();
            $table->string('call_attendees')->nullable();
            $table->text('call_summary')->nullable();
            $table->text('client_concerns')->nullable();
            $table->string('next_agreed_action')->nullable();
            $table->foreignId('snapshot_submission_id')->nullable()->comment('Lead linked to this assessment');

            // Global fields (shared)
            $table->text('overall_assessor_comment')->nullable();
            $table->text('top_5_risks')->nullable();
            $table->text('executive_summary_override')->nullable();
            $table->text('recommended_next_step')->nullable();

            // ITSM specific global fields
            $table->string('service_criticality')->nullable();
            $table->string('service_hours')->nullable();
            $table->string('primary_support_model')->nullable();
            $table->text('vendor_landscape_summary')->nullable();
            $table->text('top_incident_themes')->nullable();
            $table->text('top_problem_themes')->nullable();
            $table->text('service_debt_notes')->nullable();
        });
        
        // Also need to add pillar level fields to assessment_pillar_scores
        Schema::table('assessment_pillar_scores', function (Blueprint $table) {
            $table->text('commentary')->nullable();
            $table->text('key_risks')->nullable();
            $table->text('immediate_actions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_pillar_scores', function (Blueprint $table) {
            $table->dropColumn([
                'commentary',
                'key_risks',
                'immediate_actions'
            ]);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'target_entity',
                'call_date',
                'call_type',
                'call_attendees',
                'call_summary',
                'client_concerns',
                'next_agreed_action',
                'snapshot_submission_id',
                'overall_assessor_comment',
                'top_5_risks',
                'executive_summary_override',
                'recommended_next_step',
                'service_criticality',
                'service_hours',
                'primary_support_model',
                'vendor_landscape_summary',
                'top_incident_themes',
                'top_problem_themes',
                'service_debt_notes'
            ]);
        });
    }
};
