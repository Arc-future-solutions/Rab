<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'delivery_stage')) {
                $table->string('delivery_stage')->nullable()->after('scoring_version');
            }

            if (! Schema::hasColumn('leads', 'service_context')) {
                $table->string('service_context')->nullable()->after('delivery_stage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            foreach (['service_context', 'delivery_stage'] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
