<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_mutes')) {
            return;
        }

        Schema::create('chat_mutes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('muted_by');
            $table->unsignedBigInteger('muted_user_id');
            $table->timestamps();
            $table->unique(['muted_by', 'muted_user_id'], 'chat_mutes_pair_unique');
            $table->index(['muted_user_id', 'muted_by'], 'chat_mutes_receiver_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_mutes');
    }
};
