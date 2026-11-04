<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('order_code')->unique(); // رمز الطلب الفريد (مثل VIP-241020-1234)
            $table->string('service_type'); // highlight_profile ($10), special_matchmaking ($100), social_boost ($25)
            $table->decimal('amount', 8, 2);
            $table->string('payment_method')->default('wallet'); // wallet, stripe, paypal, manual_payment
            $table->string('payment_status')->default('pending_payment'); // pending_payment, paid, failed, rejected
            $table->string('execution_status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->json('specifications')->nullable(); // الشروط والمواصفات التي يدخلها المستخدم
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_service_orders');
    }
};