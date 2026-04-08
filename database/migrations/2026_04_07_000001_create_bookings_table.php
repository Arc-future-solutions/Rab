<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('calendly_event_uuid')->unique();
            $table->string('calendly_invitee_uuid')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('join_url')->nullable();
            $table->string('event_type_name')->nullable();
            $table->string('status')->default('active'); // active / cancelled
            $table->string('cancellation_reason')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
