<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'password_reset_code_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_reset_code_hash')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'password_reset_code_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dateTime('password_reset_code_expires_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        $columns = [];
        if (Schema::hasColumn('users', 'password_reset_code_hash')) {
            $columns[] = 'password_reset_code_hash';
        }
        if (Schema::hasColumn('users', 'password_reset_code_expires_at')) {
            $columns[] = 'password_reset_code_expires_at';
        }

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
