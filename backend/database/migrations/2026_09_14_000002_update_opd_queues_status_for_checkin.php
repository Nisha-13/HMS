<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'pending_checkin' to status enum in opd_queues
        DB::statement("ALTER TABLE `opd_queues` MODIFY COLUMN `status` ENUM('pending_checkin', 'waiting', 'calling', 'in_room', 'completed', 'skipped') NOT NULL DEFAULT 'waiting'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `opd_queues` MODIFY COLUMN `status` ENUM('waiting', 'calling', 'in_room', 'completed', 'skipped') NOT NULL DEFAULT 'waiting'");
    }
};
