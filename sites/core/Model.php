<?php
/**
 * 베이스 모델 - CRUD 헬퍼
 */
class Model
{
    protected $db;
    protected $table = '';
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * PK로 단건 조회
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * 조건부 목록 조회
     */
    public function findAll(array $conditions = [], $orderBy = 'id DESC', $limit = 20, $offset = 0)
    {
        list($where, $params) = $this->buildWhere($conditions);
        $sql = "SELECT * FROM `{$this->table}` {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 조건부 카운트
     */
    public function count(array $conditions = [])
    {
        list($where, $params) = $this->buildWhere($conditions);
        $sql = "SELECT COUNT(*) FROM `{$this->table}` {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 삽입
     */
    public function insert(array $data)
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    /**
     * 수정
     */
    public function update($id, array $data)
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $params[] = $id;
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = ?',
            $this->table,
            implode(', ', $sets),
            $this->primaryKey
        );
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 삭제
     */
    public function delete($id)
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * WHERE 절 빌드
     * 키가 %로 감싸져 있으면 LIKE, 아니면 = 비교
     */
    protected function buildWhere(array $conditions)
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $clauses = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            // LIKE 검색: '%column%' => value
            if (substr($key, 0, 1) === '%' && substr($key, -1) === '%') {
                $col = trim($key, '%');
                $clauses[] = "`{$col}` LIKE ?";
                $params[] = '%' . $value . '%';
            } else {
                $clauses[] = "`{$key}` = ?";
                $params[] = $value;
            }
        }

        if (empty($clauses)) {
            return ['', []];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
