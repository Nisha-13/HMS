<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_no')->unique();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->date('appointment_date');
            $table->string('time_slot')->default('10:00 AM');
            $table->string('token_no')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_consultation', 'completed', 'cancelled'])->default('confirmed');
            $table->enum('type', ['opd', 'emergency', 'follow_up', 'routine'])->default('opd');
            $table->text('symptoms')->nullable();
            $table->text('patient_notes')->nullable();
            $table->decimal('fee', 10, 2)->default(50.00);
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
