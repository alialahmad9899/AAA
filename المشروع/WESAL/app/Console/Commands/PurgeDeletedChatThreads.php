<?php

namespace App\Console\Commands;

use App\Models\ChatThread;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurgeDeletedChatThreads extends Command
{
    protected $signature = 'wesal:purge-deleted-chat-threads {--days=30 : Days to retain messages after account deletion}';

    protected $description = 'Permanently delete chat messages 30 days after a participant deletes their Wesal account';

    public function handle()
    {
        $retentionDays = max(30, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($retentionDays);
        $purgedThreads = 0;
        $purgedMessages = 0;

        ChatThread::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->whereHas('sender', function ($userQuery) {
                    $userQuery->withTrashed()->whereNotNull('users.deleted_at');
                })->orWhereHas('receiver', function ($userQuery) {
                    $userQuery->withTrashed()->whereNotNull('users.deleted_at');
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($threads) use (&$purgedThreads, &$purgedMessages) {
                foreach ($threads as $thread) {
                    DB::transaction(function () use ($thread, &$purgedThreads, &$purgedMessages) {
                        $messageCount = $thread->chats()->withTrashed()->count();

                        // Permanently remove both active and previously soft-deleted
                        // messages only after the deleted account's retention period.
                        $thread->chats()->withTrashed()->forceDelete();
                        $thread->forceDelete();

                        $purgedMessages += $messageCount;
                        $purgedThreads++;
                    });
                }
            });

        $this->info("Purged {$purgedMessages} chat messages in {$purgedThreads} deleted threads older than {$retentionDays} days.");
        return self::SUCCESS;
    }
}
