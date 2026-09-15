<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('floor')->default('Floor 2');
            $table->enum('type', ['general', 'icu', 'ccu', 'pediatric', 'maternity', 'vip_suite'])->default('general');
            $table->integer('total_beds')->default(10);
            $table->decimal('daily_rate', 10, 2)->default(100.00);
            $table->timestamps();
        });

        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained('wards')->onDelete('cascade');
            $table->string('bed_number');
            $table->boolean('is_occupied')->default(false);
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('patient_name')->nullable();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
