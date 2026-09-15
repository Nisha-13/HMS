<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_no')->unique();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->text('diagnosis');
            $table->text('symptoms_summary')->nullable();
            $table->string('bp', 20)->nullable();
            $table->string('pulse', 20)->nullable();
            $table->string('temperature', 20)->nullable();
            $table->string('weight', 20)->nullable();
            $table->text('advice_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->string('medicine_name');
            $table->string('dosage')->default('1 tablet');
            $table->string('frequency')->default('1-0-1');
            $table->string('duration')->default('5 days');
            $table->string('instruction')->default('After meals');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
