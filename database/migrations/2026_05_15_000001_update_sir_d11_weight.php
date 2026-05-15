<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $frameworkIds = DB::table('assessment_frameworks')
            ->where('code', 'SIR')
            ->pluck('id');

        DB::table('assessment_pillars')
            ->whereIn('framework_id', $frameworkIds)
            ->where('code', 'D11')
            ->update(['weight' => 1.2]);
    }

    public function down(): void
    {
        $frameworkIds = DB::table('assessment_frameworks')
            ->where('code', 'SIR')
            ->pluck('id');

        DB::table('assessment_pillars')
            ->whereIn('framework_id', $frameworkIds)
            ->where('code', 'D11')
            ->update(['weight' => 0.9]);
    }
};
