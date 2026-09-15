<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_queues', function (Blueprint $table) {
            $table->id();
            $table->string('token_number');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('patient_name');
            $table->string('patient_phone')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->string('counter_no')->default('Counter 1');
            $table->enum('status', ['waiting', 'calling', 'in_room', 'completed', 'skipped'])->default('waiting');
            $table->integer('queue_order')->default(1);
            $table->timestamp('called_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_queues');
    }
};
