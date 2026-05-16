<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_questions', 'hybrid_context')) {
                $table->string('hybrid_context', 50)->nullable()->after('is_hybrid');
            }
        });

        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'scoring_version')) {
                $table->string('scoring_version')->default('1.0')->after('status');
            }
        });

        Schema::table('assessment_question_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_question_responses', 'confidence_level')) {
                $table->string('confidence_level')->default('Medium')->after('confidence');
            }
        });

        DB::table('assessment_question_responses')
            ->whereNull('confidence_level')
            ->whereNotNull('confidence')
            ->update(['confidence_level' => DB::raw('confidence')]);

        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->index(
                ['framework_id', 'level', 'is_hybrid', 'hybrid_context'],
                'idx_assessment_questions_hybrid'
            );
        });

        $sirFrameworkIds = DB::table('assessment_frameworks')
            ->where('code', 'SIR')
            ->pluck('id');

        DB::table('assessment_pillars')
            ->whereIn('framework_id', $sirFrameworkIds)
            ->where('code', 'D11')
            ->update([
                'name' => 'Service Tooling, CMDB & Knowledge Management',
                'updated_at' => now(),
            ]);

        DB::table('assessment_question_responses')
            ->where('pillar_name', 'Automation, Tooling & Knowledge')
            ->orWhere('pillar_name', 'D11 - Automation, Tooling & Knowledge')
            ->orWhere('pillar_name', 'D11 — Automation, Tooling & Knowledge')
            ->update([
                'pillar_name' => 'D11 — Service Tooling, CMDB & Knowledge Management',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->dropIndex('idx_assessment_questions_hybrid');
        });

        Schema::table('assessment_question_responses', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_question_responses', 'confidence_level')) {
                $table->dropColumn('confidence_level');
            }
        });

        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'scoring_version')) {
                $table->dropColumn('scoring_version');
            }
        });

        Schema::table('assessment_questions', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_questions', 'hybrid_context')) {
                $table->dropColumn('hybrid_context');
            }
        });
    }
};
