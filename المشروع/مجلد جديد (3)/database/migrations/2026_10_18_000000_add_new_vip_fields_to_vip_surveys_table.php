<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل التعديلات وإضافة الحقول الجديدة لقاعدة البيانات
     */
    public function up(): void
    {
        Schema::table('vip_surveys', function (Blueprint $table) {
            $table->text('about_me')->nullable();
            $table->string('origin_country_city')->nullable();
            $table->string('residence_country_city')->nullable();
            $table->integer('age')->nullable();
            $table->string('nationality')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('financial_status')->nullable();
            $table->text('partner_expectations')->nullable();
        });
    }

    /**
     * التراجع عن التعديلات في حال الحاجة
     */
    public function down(): void
    {
        Schema::table('vip_surveys', function (Blueprint $table) {
            $table->dropColumn([
                'about_me',
                'origin_country_city',
                'residence_country_city',
                'age',
                'nationality',
                'marital_status',
                'financial_status',
                'partner_expectations'
            ]);
        });
    }
};