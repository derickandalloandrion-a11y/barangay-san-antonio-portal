<?php
declare(strict_types=1);

/** Read-only schema capability checks. Never changes database objects. */
final class SchemaInspector
{
    private array $columns = [];
    private array $tables = [];

    public function __construct(private PDO $db) {}

    public function hasTable(string $table): bool
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
        if (!array_key_exists($table, $this->tables)) {
            $q = $this->db->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table LIMIT 1');
            $q->execute(['table' => $table]);
            $this->tables[$table] = (bool)$q->fetchColumn();
        }
        return $this->tables[$table];
    }

    public function hasColumns(string $table, array $columns): bool
    {
        if (!$this->hasTable($table)) return false;
        foreach ($columns as $column) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$column)) return false;
            $key = $table . '.' . $column;
            if (!array_key_exists($key, $this->columns)) {
                $q = $this->db->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:table AND column_name=:column LIMIT 1');
                $q->execute(['table' => $table, 'column' => $column]);
                $this->columns[$key] = (bool)$q->fetchColumn();
            }
            if (!$this->columns[$key]) return false;
        }
        return true;
    }
}
