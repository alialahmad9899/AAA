<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionLifecycleTracking extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('package_payments') && !Schema::hasColumn('package_payments', 'activation_key')) {
            Schema::table('package_payments', function (Blueprint $table) {
                $table->string('activation_key', 64)->nullable();
                $table->unique('activation_key', 'package_payments_activation_key_unique');
            });
        }

        if (!Schema::hasTable('gateway_wallet_credits')) {
            Schema::create('gateway_wallet_credits', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('gateway', 32);
                $table->string('transaction_reference', 128);
                $table->decimal('amount', 12, 2);
                $table->string('currency', 8)->nullable();
                $table->text('payment_details')->nullable();
                $table->unsignedBigInteger('wallet_id')->nullable();
                $table->timestamps();
                $table->unique(
                    ['gateway', 'transaction_reference'],
                    'gateway_wallet_credits_gateway_reference_unique'
                );
                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('subscription_events')) {
            Schema::create('subscription_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('member_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('event_type', 50);
                $table->date('subscription_expires_at');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('details')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('push_notified_at')->nullable();
                $table->timestamps();
                $table->unique(
                    ['member_id', 'event_type', 'subscription_expires_at'],
                    'subscription_events_member_type_expiry_unique'
                );
                $table->index(['user_id', 'event_type']);
                $table->index(['event_type', 'notified_at']);
            });
        }

        if (Schema::hasTable('subscription_events') && !Schema::hasColumn('subscription_events', 'push_notified_at')) {
            Schema::table('subscription_events', function (Blueprint $table) {
                $table->timestamp('push_notified_at')->nullable()->after('notified_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('gateway_wallet_credits');

        if (Schema::hasTable('package_payments') && Schema::hasColumn('package_payments', 'activation_key')) {
            Schema::table('package_payments', function (Blueprint $table) {
                $table->dropUnique('package_payments_activation_key_unique');
                $table->dropColumn('activation_key');
            });
        }
    }
}
