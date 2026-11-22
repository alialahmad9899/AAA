<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\InterestService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InterestOwnershipDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('express_interests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('interested_by');
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('chat_threads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('thread_code')->nullable();
            $table->unsignedBigInteger('sender_user_id');
            $table->unsignedBigInteger('receiver_user_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_actor_a_cannot_accept_or_reject_requests_addressed_to_actor_b(): void
    {
        $actorA = User::create([
            'first_name' => 'Actor',
            'last_name' => 'A',
            'email' => 'actor-a@example.test',
            'password' => bcrypt('not-used'),
        ]);
        $actorB = User::create([
            'first_name' => 'Actor',
            'last_name' => 'B',
            'email' => 'actor-b@example.test',
            'password' => bcrypt('not-used'),
        ]);
        $sender = User::create([
            'first_name' => 'Sender',
            'last_name' => 'C',
            'email' => 'sender-c@example.test',
            'password' => bcrypt('not-used'),
        ]);

        $acceptInterestId = DB::table('express_interests')->insertGetId([
            'user_id' => $actorB->id,
            'interested_by' => $sender->id,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $rejectInterestId = DB::table('express_interests')->insertGetId([
            'user_id' => $actorB->id,
            'interested_by' => $sender->id,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::shouldUse('web');
        Auth::guard('web')->setUser($actorA);
        Log::shouldReceive('error')->never();

        $service = new InterestService();

        self::assertSame((int) $actorA->id, (int) auth()->id());
        self::assertNotSame((int) $actorA->id, (int) $actorB->id);
        self::assertFalse($service->accept($acceptInterestId));
        self::assertFalse($service->reject($rejectInterestId));

        self::assertSame(
            0,
            (int) DB::table('express_interests')->where('id', $acceptInterestId)->value('status')
        );
        self::assertSame(
            0,
            (int) DB::table('express_interests')->where('id', $rejectInterestId)->value('status')
        );
        self::assertSame(
            2,
            DB::table('express_interests')->where('user_id', $actorB->id)->count()
        );
        self::assertSame(0, DB::table('chat_threads')->count());
    }
}
