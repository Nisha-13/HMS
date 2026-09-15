<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('patient_profiles', 'cnic')) {
            Schema::table('patient_profiles', function (Blueprint $table) {
                $table->string('cnic', 20)->nullable()->index()->after('mrn');
            });
        }

        if (!Schema::hasColumn('appointments', 'cnic')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('cnic', 20)->nullable()->index()->after('patient_id');
            });
        }

        if (!Schema::hasColumn('opd_queues', 'cnic')) {
            Schema::table('opd_queues', function (Blueprint $table) {
                $table->string('cnic', 20)->nullable()->index()->after('patient_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('patient_profiles', 'cnic')) {
            Schema::table('patient_profiles', function (Blueprint $table) {
                $table->dropColumn('cnic');
            });
        }

        if (Schema::hasColumn('appointments', 'cnic')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('cnic');
            });
        }

        if (Schema::hasColumn('opd_queues', 'cnic')) {
            Schema::table('opd_queues', function (Blueprint $table) {
                $table->dropColumn('cnic');
            });
        }
    }
};
