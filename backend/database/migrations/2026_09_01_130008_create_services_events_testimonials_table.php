<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->decimal('starting_price', 10, 2)->default(0.00);
            $table->string('department_name')->nullable();
            $table->boolean('is_featured')->default(true);
            $table->timestamps();
        });

        Schema::create('hospital_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('Health Camp');
            $table->date('event_date');
            $table->string('time_slot')->default('10:00 AM - 04:00 PM');
            $table->string('location')->default('HealthPulse Main Auditorium');
            $table->text('description');
            $table->string('image')->nullable();
            $table->string('organizer')->default('HealthPulse Community Team');
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('patient_name');
            $table->string('patient_role')->default('Recovered Patient');
            $table->string('department_name')->nullable();
            $table->text('comment');
            $table->integer('rating')->default(5);
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('hospital_events');
        Schema::dropIfExists('services');
    }
};
