<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_announcements')) {
            return;
        }

        Schema::create('app_announcements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('announcement_type', 20)->default('note');
            $table->string('title_ar', 150);
            $table->text('message_ar');
            $table->string('title_en', 150)->nullable();
            $table->text('message_en')->nullable();
            $table->string('target_type', 20)->default('all');
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->boolean('button_enabled')->default(false);
            $table->string('button_text_ar', 80)->nullable();
            $table->string('button_text_en', 80)->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_announcements');
    }
};
