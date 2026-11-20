<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    /**
     * Restore the idempotency column on package_payments when production
     * missed the earlier subscription lifecycle migration.
     */
    public function up(): void
    {
        if (!Schema::hasTable('package_payments')) {
            throw new RuntimeException('The package_payments table must exist before adding payment activation keys.');
        }

        if (!Schema::hasColumn('package_payments', 'activation_key')) {
            Schema::table('package_payments', function (Blueprint $table) {
                $table->string('activation_key', 64)->nullable()->after('payment_method');
                $table->unique('activation_key', 'package_payments_activation_key_unique');
            });

            return;
        }

        // The column can exist after a partially applied/manual schema update.
        // Verify a single-column unique index before assuming idempotency is safe.
        $indexes = DB::select(
            "SHOW INDEX FROM `package_payments` WHERE `Column_name` = ?",
            ['activation_key']
        );

        $indexColumns = [];
        foreach ($indexes as $index) {
            if ((int) $index->Non_unique === 0) {
                $indexName = (string) $index->Key_name;
                $indexColumns[$indexName][(int) $index->Seq_in_index] = (string) $index->Column_name;
            }
        }

        $hasSingleColumnUniqueIndex = false;
        foreach ($indexColumns as $columns) {
            ksort($columns);
            if (array_values($columns) === ['activation_key']) {
                $hasSingleColumnUniqueIndex = true;
                break;
            }
        }

        if (!$hasSingleColumnUniqueIndex) {
            Schema::table('package_payments', function (Blueprint $table) {
                $table->unique('activation_key', 'package_payments_activation_key_unique');
            });
        }
    }

    /**
     * Keep the idempotency key and its values on rollback. Removing them
     * could permit an already-submitted payment to be activated twice.
     */
    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
