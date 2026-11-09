<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registration_verification_codes')
            && !Schema::hasColumn('registration_verification_codes', 'expires_at')) {
            Schema::table('registration_verification_codes', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('registration_verification_codes')
            && Schema::hasColumn('registration_verification_codes', 'expires_at')) {
            Schema::table('registration_verification_codes', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};
