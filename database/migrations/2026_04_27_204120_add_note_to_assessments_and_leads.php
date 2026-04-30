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
            $table->text('note')->nullable()->after('status');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->text('note')->nullable()->after('answers_json');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
