<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة لجدول الأعضاء
            if (!Schema::hasColumn('members', 'remaining_profile_view')) {
                $table->integer('remaining_profile_view')->default(0)->after('remaining_photo_gallery');
            }
            
            if (!Schema::hasColumn('members', 'remaining_profile_image_view')) {
                $table->integer('remaining_profile_image_view')->default(0)->after('remaining_profile_view');
            }

            if (!Schema::hasColumn('members', 'remaining_gallery_image_view')) {
                $table->integer('remaining_gallery_image_view')->default(0)->after('remaining_profile_image_view');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // حذف الأعمدة في حال تراجعنا عن الـ Migration
            $table->dropColumn(['remaining_profile_view', 'remaining_profile_image_view', 'remaining_gallery_image_view']);
        });
    }
};