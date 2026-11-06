<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function makeNullable(string $table, string $columnName): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) ||
            !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $columnName)) {
            throw new RuntimeException('Unsafe table or column identifier.');
        }
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $columnName)) {
            return;
        }

        $column = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type,
                    IS_NULLABLE AS is_nullable,
                    CHARACTER_SET_NAME AS character_set_name,
                    COLLATION_NAME AS collation_name,
                    COLUMN_DEFAULT AS column_default,
                    COLUMN_COMMENT AS column_comment
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $columnName]
        );

        if (!$column || strtoupper((string) ($column->is_nullable ?? '')) === 'YES') {
            return;
        }

        $type = $column->column_type ?? null;
        if (!is_string($type) || !preg_match('/^[A-Za-z0-9_(), ]+$/', $type)) {
            throw new RuntimeException('Cannot safely inspect column type for ' . $table . '.' . $columnName);
        }

        $definition = $type;
        foreach ([
            'character_set_name' => 'CHARACTER SET',
            'collation_name' => 'COLLATE',
        ] as $property => $keyword) {
            $value = $column->{$property} ?? null;
            if ($value !== null && $value !== '') {
                if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $value)) {
                    throw new RuntimeException('Unsafe column metadata for ' . $table . '.' . $columnName);
                }
                $definition .= ' ' . $keyword . ' ' . $value;
            }
        }

        $definition .= ' NULL';
        if ($column->column_default !== null) {
            $definition .= ' DEFAULT ' . DB::connection()->getPdo()->quote((string) $column->column_default);
        }
        if (!empty($column->column_comment)) {
            $definition .= ' COMMENT ' . DB::connection()->getPdo()->quote((string) $column->column_comment);
        }

        DB::statement('ALTER TABLE ' . $table . ' MODIFY ' . $columnName . ' ' . $definition);
    }

    public function up(): void
    {
        $this->makeNullable('users', 'phone');

        foreach (['education', 'educations', 'career', 'careers'] as $table) {
            $this->makeNullable($table, 'start');
        }
    }

    public function down(): void
    {
        // Keep these columns nullable: profile year fields and phone are optional.
    }
};
