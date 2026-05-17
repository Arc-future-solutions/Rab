<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('consent_given')->default(false)->after('phone');
            $table->timestamp('consent_timestamp')->nullable()->after('consent_given');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'consent_given',
                'consent_timestamp',
            ]);
        });
    }
};
