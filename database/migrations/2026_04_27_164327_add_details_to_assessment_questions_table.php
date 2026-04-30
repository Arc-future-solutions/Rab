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
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->text('hidden_risk')->nullable()->after('question_text');
            $table->text('stage_note')->nullable()->after('hidden_risk');
            $table->integer('question_type')->nullable()->after('stage_note');
            $table->string('question_type_label')->nullable()->after('question_type');
            $table->json('score_anchors')->nullable()->after('question_type_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->dropColumn(['hidden_risk', 'stage_note', 'question_type', 'question_type_label', 'score_anchors']);
        });
    }
};
