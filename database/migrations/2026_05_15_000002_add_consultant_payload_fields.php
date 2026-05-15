<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'delivery_stage')) {
                $table->string('delivery_stage')->nullable()->after('target_entity');
            }
            if (!Schema::hasColumn('assessments', 'service_context')) {
                $table->string('service_context')->nullable()->after('delivery_stage');
            }
            if (!Schema::hasColumn('assessments', 'regulatory_context')) {
                $table->string('regulatory_context')->nullable()->after('service_context');
            }
            if (!Schema::hasColumn('assessments', 'sponsor_name')) {
                $table->string('sponsor_name')->nullable()->after('regulatory_context');
            }
            if (!Schema::hasColumn('assessments', 'interview_count')) {
                $table->unsignedInteger('interview_count')->nullable()->after('call_attendees');
            }
            if (!Schema::hasColumn('assessments', 'documents_reviewed')) {
                $table->json('documents_reviewed')->nullable()->after('interview_count');
            }
            if (!Schema::hasColumn('assessments', 'programme_value')) {
                $table->decimal('programme_value', 14, 2)->nullable()->after('documents_reviewed');
            }
            if (!Schema::hasColumn('assessments', 'annual_service_cost')) {
                $table->decimal('annual_service_cost', 14, 2)->nullable()->after('programme_value');
            }
            if (!Schema::hasColumn('assessments', 'reporting_accuracy_risk')) {
                $table->boolean('reporting_accuracy_risk')->default(false)->after('annual_service_cost');
            }
            if (!Schema::hasColumn('assessments', 'reporting_accuracy_evidence')) {
                $table->text('reporting_accuracy_evidence')->nullable()->after('reporting_accuracy_risk');
            }
            if (!Schema::hasColumn('assessments', 'sponsor_position')) {
                $table->text('sponsor_position')->nullable()->after('reporting_accuracy_evidence');
            }
            if (!Schema::hasColumn('assessments', 'operational_position')) {
                $table->text('operational_position')->nullable()->after('sponsor_position');
            }
            if (!Schema::hasColumn('assessments', 'divergence_areas')) {
                $table->json('divergence_areas')->nullable()->after('operational_position');
            }
        });

        Schema::table('assessment_question_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_question_responses', 'source_type')) {
                $table->string('source_type')->nullable()->after('evidence_note');
            }
            if (!Schema::hasColumn('assessment_question_responses', 'respondent_role')) {
                $table->string('respondent_role')->nullable()->after('source_type');
            }
            if (!Schema::hasColumn('assessment_question_responses', 'document_source')) {
                $table->string('document_source')->nullable()->after('respondent_role');
            }
            if (!Schema::hasColumn('assessment_question_responses', 'stakeholder_divergence_note')) {
                $table->text('stakeholder_divergence_note')->nullable()->after('document_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_question_responses', function (Blueprint $table) {
            $table->dropColumn([
                'source_type',
                'respondent_role',
                'document_source',
                'stakeholder_divergence_note',
            ]);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_stage',
                'service_context',
                'regulatory_context',
                'sponsor_name',
                'interview_count',
                'documents_reviewed',
                'programme_value',
                'annual_service_cost',
                'reporting_accuracy_risk',
                'reporting_accuracy_evidence',
                'sponsor_position',
                'operational_position',
                'divergence_areas',
            ]);
        });
    }
};
