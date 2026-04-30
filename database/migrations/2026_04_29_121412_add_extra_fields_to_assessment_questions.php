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
            $table->boolean('is_compliance')->default(false)->after('score_anchors');
            $table->boolean('is_hybrid')->default(false)->after('is_compliance');
            $table->integer('version')->default(1)->after('is_hybrid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            //
        });
    }
};
