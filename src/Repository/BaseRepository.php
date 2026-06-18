<?php
namespace App\Repository;

use App\Database\Connection;
use PDO;
use BadMethodCallException;

abstract class BaseRepository {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $insertOnly = false;

    public function __construct() {
        $this->db = Connection::getConnection();
    }

    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function all(): array {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function create(array $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data): bool {
        if ($this->insertOnly) {
            throw new BadMethodCallException("Update operation is not allowed on insert-only table '{$this->table}'.");
        }

        $fields = '';
        foreach (array_keys($data) as $key) {
            $fields .= "{$key} = :{$key}, ";
        }
        $fields = rtrim($fields, ', ');
        
        $sql = "UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = :pk_id";
        
        $stmt = $this->db->prepare($sql);
        $data['pk_id'] = $id;
        return $stmt->execute($data);
    }

    public function delete($id): bool {
        if ($this->insertOnly) {
            throw new BadMethodCallException("Delete operation is not allowed on insert-only table '{$this->table}'.");
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
