<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('pass_code')->unique();
            $table->foreignId('event_id')->constrained('hospital_events')->onDelete('cascade');
            $table->string('attendee_name');
            $table->string('attendee_phone');
            $table->string('cnic_id');
            $table->string('age_group');
            $table->string('health_concern');
            $table->string('attendee_email')->nullable();
            $table->integer('attendees_count')->default(1);
            $table->string('status')->default('confirmed'); // confirmed, attended, cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
