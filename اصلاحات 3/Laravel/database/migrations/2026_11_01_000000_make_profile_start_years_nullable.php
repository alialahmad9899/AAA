<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['education', 'educations', 'career', 'careers'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'start')) continue;
            $column = DB::selectOne(
                'SELECT COLUMN_TYPE AS column_type FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, 'start']
            );
            $type = $column->column_type ?? null;
            if (!is_string($type) || !preg_match('/^[A-Za-z0-9_(), ]+$/', $type)) {
                throw new RuntimeException('Cannot safely inspect start column type for ' . $table);
            }
            DB::statement('ALTER TABLE ' . $table . ' MODIFY start ' . $type . ' NULL');
        }
    }
    public function down(): void
    {
        // Keep fields nullable; new profile records may not contain start years.
    }
};
