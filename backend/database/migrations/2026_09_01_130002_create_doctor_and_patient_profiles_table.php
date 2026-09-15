<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('specialty');
            $table->string('qualification');
            $table->integer('experience_years')->default(5);
            $table->decimal('consultation_fee', 10, 2)->default(50.00);
            $table->string('room_no')->default('Room 101');
            $table->text('bio')->nullable();
            $table->string('available_days')->default('Mon - Sat');
            $table->time('available_time_start')->default('09:00:00');
            $table->time('available_time_end')->default('17:00:00');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('mrn')->unique(); // Medical Record Number
            $table->string('blood_group', 10)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_profiles');
        Schema::dropIfExists('doctor_profiles');
    }
};
