<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'booking_token')) {
                $table->uuid('booking_token')->nullable()->unique()->after('booking_status');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'lead_id')) {
                $table->foreignId('lead_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('leads')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'lead_id')) {
                $table->dropConstrainedForeignId('lead_id');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'booking_token')) {
                $table->dropUnique(['booking_token']);
                $table->dropColumn('booking_token');
            }
        });
    }
};
