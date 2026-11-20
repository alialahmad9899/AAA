<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_announcements')
            || Schema::hasColumn('app_announcements', 'target_gender')) {
            return;
        }

        Schema::table('app_announcements', function (Blueprint $table) {
            $table->string('target_gender', 10)
                ->default('all')
                ->after('target_type');
        });
    }

    public function down(): void
    {
        // Keep audience settings intact on rollback; dropping this field could
        // silently broaden an active announcement's audience after a later deploy.
    }
};
