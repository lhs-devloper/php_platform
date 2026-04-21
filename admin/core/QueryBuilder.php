<?php
/**
 * Fluent Query Builder
 * PDO 기반 ORM 스타일 쿼리 빌더
 */
class QueryBuilder
{
    private $db;
    private $table;
    private $alias;

    private $selects = [];
    private $joins = [];
    private $wheres = [];
    private $params = [];
    private $orderBys = [];
    private $groupBys = [];
    private $havings = [];
    private $havingParams = [];
    private $limitVal = null;
    private $offsetVal = null;

    public function __construct(string $table, string $alias = '', $db = null)
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->db = $db ?: Database::getInstance();
    }

    /**
     * SELECT 절 지정
     */
    public function select(string $columns): self
    {
        $this->selects[] = $columns;
        return $this;
    }

    /**
     * raw SELECT 표현식 추가 (GROUP_CONCAT, SUM 등)
     */
    public function selectRaw(string $expr, array $params = []): self
    {
        $this->selects[] = $expr;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    // ─── JOIN ───

    public function join(string $table, string $on, array $params = []): self
    {
        $this->joins[] = ['type' => 'JOIN', 'table' => $table, 'on' => $on, 'params' => $params];
        return $this;
    }

    public function leftJoin(string $table, string $on, array $params = []): self
    {
        $this->joins[] = ['type' => 'LEFT JOIN', 'table' => $table, 'on' => $on, 'params' => $params];
        return $this;
    }

    // ─── WHERE ───

    /**
     * 기본 WHERE 조건
     * where('status', 'ACTIVE')           → status = ?
     * where('status', '!=', 'TERMINATED') → status != ?
     */
    public function where(string $col, $operatorOrValue, $value = null): self
    {
        if ($value === null) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        if ($value === null) {
            return $this;
        }

        $this->wheres[] = "`{$col}` {$operator} ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * 컬럼명에 테이블 별칭 포함된 WHERE (backtick 없이)
     * whereColumn('t.status', 'ACTIVE')
     */
    public function whereColumn(string $expr, $operatorOrValue, $value = null): self
    {
        if ($value === null) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        if ($value === null) {
            return $this;
        }

        $this->wheres[] = "{$expr} {$operator} ?";
        $this->params[] = $value;
        return $this;
    }

    /**
     * Raw WHERE 절 (복합 조건)
     */
    public function whereRaw(string $sql, array $params = []): self
    {
        $this->wheres[] = $sql;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * WHERE IN
     */
    public function whereIn(string $col, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = '1 = 0';
            return $this;
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$col} IN ({$placeholders})";
        $this->params = array_merge($this->params, array_values($values));
        return $this;
    }

    /**
     * WHERE NOT IN
     */
    public function whereNotIn(string $col, string $subSql, array $params = []): self
    {
        $this->wheres[] = "{$col} NOT IN ({$subSql})";
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * LIKE 검색 (단일 컬럼)
     */
    public function whereLike(string $col, string $value): self
    {
        if ($value === '') {
            return $this;
        }
        $this->wheres[] = "{$col} LIKE ?";
        $this->params[] = "%{$value}%";
        return $this;
    }

    /**
     * 여러 컬럼에 대한 OR LIKE 검색
     * whereMultiLike(['t.company_name', 't.ceo_name'], $keyword)
     * → (t.company_name LIKE ? OR t.ceo_name LIKE ?)
     */
    public function whereMultiLike(array $cols, string $value): self
    {
        if ($value === '' || empty($cols)) {
            return $this;
        }
        $parts = [];
        foreach ($cols as $col) {
            $parts[] = "{$col} LIKE ?";
            $this->params[] = "%{$value}%";
        }
        $this->wheres[] = '(' . implode(' OR ', $parts) . ')';
        return $this;
    }

    /**
     * WHERE EXISTS 서브쿼리
     */
    public function whereExists(string $subSql, array $params = []): self
    {
        $this->wheres[] = "EXISTS ({$subSql})";
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * 조건이 true일 때만 WHERE 추가
     */
    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    // ─── ORDER / GROUP / LIMIT ───

    public function orderBy(string $expr): self
    {
        $this->orderBys[] = $expr;
        return $this;
    }

    public function groupBy(string $expr): self
    {
        $this->groupBys[] = $expr;
        return $this;
    }

    public function having(string $expr, array $params = []): self
    {
        $this->havings[] = $expr;
        $this->havingParams = array_merge($this->havingParams, $params);
        return $this;
    }

    public function limit(int $n): self
    {
        $this->limitVal = $n;
        return $this;
    }

    public function offset(int $n): self
    {
        $this->offsetVal = $n;
        return $this;
    }

    // ─── 결과 반환 ───

    /**
     * SELECT 실행 → 전체 결과 배열
     */
    public function get(): array
    {
        list($sql, $params) = $this->buildSelect();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * SELECT 실행 → 단건
     */
    public function first()
    {
        $this->limitVal = 1;
        list($sql, $params) = $this->buildSelect();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * COUNT(*) 조회
     */
    public function count(): int
    {
        list($sql, $params) = $this->buildCount();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 단일 컬럼 값 조회
     */
    public function value(string $col)
    {
        $origSelects = $this->selects;
        $this->selects = [$col];
        $this->limitVal = 1;
        list($sql, $params) = $this->buildSelect();
        $this->selects = $origSelects;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : null;
    }

    /**
     * 단일 컬럼을 배열로 조회 (PDO::FETCH_COLUMN)
     */
    public function pluck(string $col): array
    {
        $origSelects = $this->selects;
        $this->selects = [$col];
        list($sql, $params) = $this->buildSelect();
        $this->selects = $origSelects;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 페이지네이션 (COUNT + 데이터 조회 통합)
     * @return array ['rows' => [], 'total' => int]
     */
    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $total = $this->count();

        $this->limitVal = $perPage;
        $this->offsetVal = ($page - 1) * $perPage;
        list($sql, $params) = $this->buildSelect();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    // ─── CUD ───

    /**
     * INSERT
     * @return int lastInsertId
     */
    public function insert(array $data): int
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
     * UPDATE (WHERE 조건 필수)
     */
    public function update(array $data): bool
    {
        if (empty($this->wheres)) {
            throw new RuntimeException('QueryBuilder::update() requires at least one WHERE condition.');
        }

        $sets = [];
        $setParams = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $setParams[] = $val;
        }

        $whereStr = $this->buildWhereClause();
        $sql = sprintf('UPDATE `%s`%s SET %s %s',
            $this->table,
            $this->alias ? ' ' . $this->alias : '',
            implode(', ', $sets),
            $whereStr
        );

        $allParams = array_merge($setParams, $this->params);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($allParams);
    }

    /**
     * Raw UPDATE (SET 절에 NOW() 등 표현식 포함)
     */
    public function updateRaw(string $setClause, array $params = []): bool
    {
        if (empty($this->wheres)) {
            throw new RuntimeException('QueryBuilder::updateRaw() requires at least one WHERE condition.');
        }

        $whereStr = $this->buildWhereClause();
        $sql = sprintf('UPDATE `%s`%s SET %s %s',
            $this->table,
            $this->alias ? ' ' . $this->alias : '',
            $setClause,
            $whereStr
        );

        $allParams = array_merge($params, $this->params);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($allParams);
    }

    /**
     * DELETE (WHERE 조건 필수)
     */
    public function delete(): bool
    {
        if (empty($this->wheres)) {
            throw new RuntimeException('QueryBuilder::delete() requires at least one WHERE condition.');
        }

        $whereStr = $this->buildWhereClause();
        $sql = sprintf('DELETE FROM `%s` %s', $this->table, $whereStr);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($this->params);
    }

    // ─── Raw 쿼리 ───

    /**
     * Raw SQL 실행 (SELECT)
     */
    public static function raw(string $sql, array $params = [], $db = null): array
    {
        $pdo = $db ?: Database::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Raw SQL 실행 → 단건
     */
    public static function rawFirst(string $sql, array $params = [], $db = null)
    {
        $pdo = $db ?: Database::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Raw SQL 실행 → 스칼라값
     */
    public static function rawValue(string $sql, array $params = [], $db = null)
    {
        $pdo = $db ?: Database::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : null;
    }

    /**
     * Raw SQL 실행 (INSERT/UPDATE/DELETE)
     */
    public static function rawExecute(string $sql, array $params = [], $db = null): bool
    {
        $pdo = $db ?: Database::getInstance();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // ─── 내부 빌드 메서드 ───

    private function buildSelect(): array
    {
        $selectStr = empty($this->selects) ? '*' : implode(', ', $this->selects);
        $tableRef = "`{$this->table}`" . ($this->alias ? " {$this->alias}" : '');

        $sql = "SELECT {$selectStr} FROM {$tableRef}";

        $allParams = [];

        // JOIN
        foreach ($this->joins as $j) {
            $sql .= " {$j['type']} {$j['table']} ON {$j['on']}";
            $allParams = array_merge($allParams, $j['params']);
        }

        // WHERE
        $allParams = array_merge($allParams, $this->params);
        $whereStr = $this->buildWhereClause();
        if ($whereStr !== '') {
            $sql .= " {$whereStr}";
        }

        // GROUP BY
        if (!empty($this->groupBys)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBys);
        }

        // HAVING
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
            $allParams = array_merge($allParams, $this->havingParams);
        }

        // ORDER BY
        if (!empty($this->orderBys)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        // LIMIT / OFFSET
        if ($this->limitVal !== null) {
            $sql .= ' LIMIT ?';
            $allParams[] = (int)$this->limitVal;
        }
        if ($this->offsetVal !== null) {
            $sql .= ' OFFSET ?';
            $allParams[] = (int)$this->offsetVal;
        }

        return [$sql, $allParams];
    }

    private function buildCount(): array
    {
        $tableRef = "`{$this->table}`" . ($this->alias ? " {$this->alias}" : '');
        $sql = "SELECT COUNT(*) FROM {$tableRef}";

        $allParams = [];

        // JOIN (COUNT에서도 JOIN이 필요할 수 있음)
        foreach ($this->joins as $j) {
            $sql .= " {$j['type']} {$j['table']} ON {$j['on']}";
            $allParams = array_merge($allParams, $j['params']);
        }

        $allParams = array_merge($allParams, $this->params);
        $whereStr = $this->buildWhereClause();
        if ($whereStr !== '') {
            $sql .= " {$whereStr}";
        }

        // GROUP BY
        if (!empty($this->groupBys)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBys);
        }

        // HAVING
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
            $allParams = array_merge($allParams, $this->havingParams);
        }

        return [$sql, $allParams];
    }

    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        return 'WHERE ' . implode(' AND ', $this->wheres);
    }

    /**
     * PDO 인스턴스 반환 (raw 쿼리용)
     */
    public function getDb()
    {
        return $this->db;
    }
}
