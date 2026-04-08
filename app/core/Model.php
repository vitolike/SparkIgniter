<?php
// app/core/Model.php
#[\AllowDynamicProperties]
abstract class Model {
    protected PDO $db;
    protected DB  $qb;  
    protected string $table;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->qb = new DB($db); // <-- 1 linha
    }

    public function find($id, string $pk='id'): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$pk} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(int $limit=100, int $offset=0): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insert(array $data): int {
        $cols = array_keys($data);
        $fields = implode(',', $cols);
        $place = ':' . implode(',:', $cols);

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($place)";
        $returningId = false;

        if ($driver === 'pgsql' && !isset($data['id'])) {
            $sql .= " RETURNING id";
            $returningId = true;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        if ($driver === 'pgsql' && $returningId) {
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : 0;
        } else {
            $id = $this->db->lastInsertId();
            return (int)($id ?: 0);
        }
    }

    public function update($id, array $data, string $pk='id'): bool {
        $sets = [];
        foreach ($data as $k => $v) $sets.append
        ;
        return true;
    }
}