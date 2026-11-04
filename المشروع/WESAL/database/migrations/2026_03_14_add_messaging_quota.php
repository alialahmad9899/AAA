<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // إضافة الحقول لجدول الباقات الحالي
        Schema::table('packages', function (Blueprint $table) {
            $table->integer('message_profile_quota')->default(-1);
            $table->enum('message_quota_period', ['lifetime', 'monthly', 'daily'])->default('lifetime');
            $table->decimal('message_cost', 10, 2)->default(0);
            $table->string('message_cost_type')->default('balance');
        });

        // إنشاء جدول جديد تماماً لتتبع مراسلات المستخدمين
        Schema::create('message_quota_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('recipient_id');
            $table->timestamp('first_contact_at')->useCurrent();
            $table->timestamp('last_message_at')->useCurrent();
            
            // هذا السطر يضمن أن النظام يحسب المستلم مرة واحدة فقط لكل مرسل
            $table->unique(['user_id', 'recipient_id']); 
        });
    }

    public function down()
    {
        // لحذف التعديلات في حال أردنا العودة للوراء
        Schema::dropIfExists('message_quota_usage');
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['message_profile_quota', 'message_quota_period', 'message_cost', 'message_cost_type']);
        });
    }
};