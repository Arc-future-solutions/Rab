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
            $table->decimal('dmi', 8, 2)->nullable()->after('bau_readiness');
            $table->decimal('chi', 8, 2)->nullable()->after('dmi');
            $table->decimal('simi', 8, 2)->nullable()->after('chi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['dmi', 'chi', 'simi']);
        });
    }
};
