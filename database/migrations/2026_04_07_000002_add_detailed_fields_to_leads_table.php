<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('email')->nullable()->after('company');
            $table->string('phone')->nullable()->after('email');
            $table->string('role_title')->nullable()->after('phone');
            $table->string('industry')->nullable()->after('role_title');
            $table->text('free_text_concern')->nullable()->after('industry');
            $table->string('confidence_level')->nullable()->after('free_text_concern');
            $table->json('index_scores_json')->nullable()->after('priority');
            $table->json('answers_json')->nullable()->after('index_scores_json');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'phone',
                'role_title',
                'industry',
                'free_text_concern',
                'confidence_level',
                'index_scores_json',
                'answers_json',
            ]);
        });
    }
};
