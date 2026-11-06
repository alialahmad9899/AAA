<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_payment_id')->nullable();
            
            // معلومات العضو الشخصية
            $table->string('occupation')->nullable();        // العمل
            $table->string('residence')->nullable();         // مكان الإقامة
            $table->string('housing_type')->nullable();      // نوع السكن
            $table->string('qualification')->nullable();     // المؤهل العلمي
            $table->integer('height')->nullable();           // الطول (سم)
            $table->integer('weight')->nullable();           // الوزن (كغ)
            $table->string('religion')->nullable();          // الديانة

            // مواصفات الشريك المطلوب
            $table->integer('partner_min_height')->nullable();
            $table->integer('partner_max_height')->nullable();
            $table->integer('partner_min_weight')->nullable();
            $table->integer('partner_max_weight')->nullable();
            $table->string('partner_eye_color')->nullable();
            $table->string('partner_religion')->nullable();

            // حالة النشر على الفيسبوك
            $table->enum('facebook_status', ['pending', 'published'])->default('pending');
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('package_payment_id')->references('id')->on('package_payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_surveys');
    }
};