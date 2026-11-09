<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registration_verification_codes')
            && !Schema::hasColumn('registration_verification_codes', 'code_hash')) {
            Schema::table('registration_verification_codes', function (Blueprint $table) {
                $table->string('code_hash')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('registration_verification_codes')
            && Schema::hasColumn('registration_verification_codes', 'code_hash')) {
            Schema::table('registration_verification_codes', function (Blueprint $table) {
                $table->dropColumn('code_hash');
            });
        }
    }
};
