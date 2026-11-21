<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reported_users') ||
            Schema::hasColumn('reported_users', 'chat_evidence')) {
            return;
        }

        Schema::table('reported_users', function (Blueprint $table) {
            $table->longText('chat_evidence')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('reported_users') ||
            !Schema::hasColumn('reported_users', 'chat_evidence')) {
            return;
        }

        Schema::table('reported_users', function (Blueprint $table) {
            $table->dropColumn('chat_evidence');
        });
    }
};
