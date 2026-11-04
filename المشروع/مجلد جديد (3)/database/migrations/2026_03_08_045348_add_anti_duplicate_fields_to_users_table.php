<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAntiDuplicateFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // التحقق مما إذا كان العمود غير موجود قبل إضافته
            if (!Schema::hasColumn('users', 'registration_ip')) {
                $table->string('registration_ip')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'device_fingerprint')) {
                $table->string('device_fingerprint')->nullable()->after('registration_ip');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['registration_ip', 'device_fingerprint']);
        });
    }
}