<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'snapshot_report_json')) {
                $table->json('snapshot_report_json')->nullable()->after('ai_recommendation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'snapshot_report_json')) {
                $table->dropColumn('snapshot_report_json');
            }
        });
    }
};
