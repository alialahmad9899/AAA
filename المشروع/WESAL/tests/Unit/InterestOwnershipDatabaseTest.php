<?php

namespace Illuminate\Support\Facades {
    class DB
    {
        public static function transaction(callable $callback)
        {
            return $callback();
        }
    }

    class Log
    {
        public static function error($message, array $context = []): void
        {
            throw new \RuntimeException('Unexpected InterestService error: ' . $message);
        }
    }
}

namespace App\Models {
    final class ExpressInterest
    {
        public static array $records = [];
        public static int $lockCount = 0;

        public static function whereKey($id): ExpressInterestQuery
        {
            return new ExpressInterestQuery((int) $id);
        }
    }

    final class ExpressInterestQuery
    {
        private int $id;
        private array $filters = [];

        public function __construct(int $id)
        {
            $this->id = $id;
        }

        public function where(string $column, $value): self
        {
            $this->filters[$column] = $value;
            return $this;
        }

        public function lockForUpdate(): self
        {
            ExpressInterest::$lockCount++;
            return $this;
        }

        public function first()
        {
            foreach (ExpressInterest::$records as $record) {
                if ((int) $record['id'] !== $this->id) {
                    continue;
                }

                $matches = true;
                foreach ($this->filters as $column => $value) {
                    if (!array_key_exists($column, $record) ||
                        (string) $record[$column] !== (string) $value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    return (object) $record;
                }
            }

            return null;
        }
    }

    final class ChatThread
    {
    }

    final class User
    {
    }
}

namespace {
    function auth()
    {
        return new class {
            public function id(): int
            {
                return \Tests\Unit\InterestOwnershipFakeAuth::$actorId;
            }
        };
    }
}

namespace Tests\Unit {
    use App\Models\ExpressInterest;
    use App\Services\InterestService;
    use PHPUnit\Framework\TestCase;

    final class InterestOwnershipFakeAuth
    {
        public static int $actorId = 0;
    }

    class InterestOwnershipDatabaseTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            ExpressInterest::$records = [
                [
                    'id' => 41,
                    'user_id' => 200, // Actor B owns this incoming request.
                    'interested_by' => 300,
                    'status' => 0,
                ],
                [
                    'id' => 42,
                    'user_id' => 200, // Actor B owns this incoming request too.
                    'interested_by' => 301,
                    'status' => 0,
                ],
            ];
            ExpressInterest::$lockCount = 0;
            InterestOwnershipFakeAuth::$actorId = 100; // Actor A is authenticated.
        }

        public function test_actor_a_cannot_accept_or_reject_requests_owned_by_actor_b(): void
        {
            $service = new InterestService();

            self::assertFalse($service->accept(41));
            self::assertFalse($service->reject(42));

            self::assertSame(2, ExpressInterest::$lockCount);
            self::assertCount(2, ExpressInterest::$records);
            self::assertSame(0, ExpressInterest::$records[0]['status']);
            self::assertSame(0, ExpressInterest::$records[1]['status']);
            self::assertSame(200, ExpressInterest::$records[0]['user_id']);
            self::assertSame(200, ExpressInterest::$records[1]['user_id']);
        }

        public function test_missing_and_non_pending_requests_are_not_changed(): void
        {
            ExpressInterest::$records[0]['status'] = 1;
            $service = new InterestService();

            self::assertFalse($service->accept(41));
            self::assertFalse($service->reject(999));
            self::assertSame(1, ExpressInterest::$records[0]['status']);
            self::assertSame(2, count(ExpressInterest::$records));
        }
    }
}
