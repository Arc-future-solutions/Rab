<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'consent_given')) {
                $table->boolean('consent_given')->default(false)->after('phone');
            }

            if (! Schema::hasColumn('leads', 'consent_timestamp')) {
                $table->timestamp('consent_timestamp')->nullable()->after('consent_given');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            foreach ([
                'consent_given',
                'consent_timestamp',
            ] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
