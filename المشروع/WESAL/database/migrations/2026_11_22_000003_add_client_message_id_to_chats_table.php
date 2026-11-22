<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('chats', 'client_message_id')) {
            return;
        }

        Schema::table('chats', function (Blueprint $table) {
            $table->string('client_message_id', 64)->nullable();
            $table->unique(
                ['sender_user_id', 'client_message_id'],
                'chats_sender_client_message_unique'
            );
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('chats', 'client_message_id')) {
            return;
        }

        Schema::table('chats', function (Blueprint $table) {
            $table->dropUnique('chats_sender_client_message_unique');
            $table->dropColumn('client_message_id');
        });
    }
};
