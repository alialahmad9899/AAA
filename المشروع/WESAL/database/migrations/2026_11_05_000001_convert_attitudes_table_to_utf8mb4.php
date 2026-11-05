<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        // The production database uses MySQL. Keep other supported test drivers unaffected.
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('attitudes')) {
            throw new RuntimeException('Cannot enable emoji support: the attitudes table does not exist.');
        }

        // Preserve existing column definitions and values while upgrading all text columns
        // in this table to a character set that supports Arabic and four-byte Unicode.
        $connection->statement(
            'ALTER TABLE `attitudes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        // Intentionally do not downgrade to utf8mb3: doing so could make stored emoji
        // values unreadable or impossible to write after this migration has run.
    }
};
