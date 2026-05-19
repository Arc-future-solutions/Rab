<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'assessment_type')) {
                $table->string('assessment_type')->nullable()->after('type');
            }

            if (! Schema::hasColumn('leads', 'booking_status')) {
                $table->string('booking_status')->default('NotBooked')->after('lead_status');
            }

            if (! Schema::hasColumn('leads', 'critical_flag')) {
                $table->string('critical_flag')->nullable()->after('rag_status');
            }

            if (! Schema::hasColumn('leads', 'action_indicator')) {
                $table->string('action_indicator')->nullable()->after('critical_flag');
            }

            if (! Schema::hasColumn('leads', 'alerts_json')) {
                $table->json('alerts_json')->nullable()->after('action_indicator');
            }

            if (! Schema::hasColumn('leads', 'top_three_insight_areas_json')) {
                $table->json('top_three_insight_areas_json')->nullable()->after('alerts_json');
            }

            if (! Schema::hasColumn('leads', 'regulatory_context')) {
                $table->string('regulatory_context')->nullable()->after('industry');
            }

            if (! Schema::hasColumn('leads', 'scoring_version')) {
                $table->string('scoring_version')->default('1.0')->after('regulatory_context');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            foreach ([
                'assessment_type',
                'booking_status',
                'critical_flag',
                'action_indicator',
                'alerts_json',
                'top_three_insight_areas_json',
                'regulatory_context',
                'scoring_version',
            ] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
