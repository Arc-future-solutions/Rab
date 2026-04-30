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
        Schema::create('assessment_frameworks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PHI, ITSM
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assessment_pillars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_id')->constrained('assessment_frameworks')->cascadeOnDelete();
            $table->string('code'); // P1, D1...
            $table->string('name');
            $table->decimal('weight', 8, 2)->default(1.0);
            $table->boolean('is_critical')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_id')->constrained('assessment_frameworks')->cascadeOnDelete();
            $table->foreignId('pillar_id')->constrained('assessment_pillars')->cascadeOnDelete();
            $table->string('level'); // snapshot, full
            $table->string('question_code'); // P1_S1, D1_F1...
            $table->text('question_text');
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessment_pillars');
        Schema::dropIfExists('assessment_frameworks');
    }
};
