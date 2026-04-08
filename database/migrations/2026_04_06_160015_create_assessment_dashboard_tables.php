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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('primary_contact');
            $table->string('email')->nullable();
            $table->string('industry')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company');
            $table->string('type'); // PHI/ITSM
            $table->decimal('overall_score', 8, 2);
            $table->string('rag_status'); // Red, Amber, Green
            $table->string('priority'); // High, Normal
            $table->boolean('converted_to_client')->default(false);
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // PHI or ITSM
            $table->decimal('overall_score', 8, 2);
            $table->string('rag_status');
            $table->string('status')->default('draft'); // draft, in_progress, approved
            
            // Optional metrics
            $table->decimal('bri', 8, 2)->nullable();
            $table->decimal('vri', 8, 2)->nullable();
            $table->decimal('ssi', 8, 2)->nullable();
            $table->decimal('smi', 8, 2)->nullable();
            $table->decimal('bau_readiness', 8, 2)->nullable();
            $table->boolean('critical_flag')->default(false);
            
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('ai_draft_json')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_pillar_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('score', 8, 2);
            $table->string('rag_status');
            $table->boolean('critical_flag')->default(false);
            $table->timestamps();
        });

        Schema::create('assessment_question_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('pillar_name')->nullable();
            $table->text('question');
            $table->integer('score');
            $table->text('evidence_note')->nullable();
            $table->string('confidence')->nullable(); // High/Med/Low
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_question_responses');
        Schema::dropIfExists('assessment_pillar_scores');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('clients');
    }
};
