<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'ai_generation_status')) {
                $table->string('ai_generation_status')->default('idle')->after('ai_recommendation');
            }

            if (! Schema::hasColumn('assessments', 'ai_generation_error')) {
                $table->text('ai_generation_error')->nullable()->after('ai_generation_status');
            }

            if (! Schema::hasColumn('assessments', 'ai_generation_started_at')) {
                $table->timestamp('ai_generation_started_at')->nullable()->after('ai_generation_error');
            }

            if (! Schema::hasColumn('assessments', 'ai_generation_completed_at')) {
                $table->timestamp('ai_generation_completed_at')->nullable()->after('ai_generation_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            foreach ([
                'ai_generation_completed_at',
                'ai_generation_started_at',
                'ai_generation_error',
                'ai_generation_status',
            ] as $column) {
                if (Schema::hasColumn('assessments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
