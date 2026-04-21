<?php
/**
 * 베이스 모델 - ORM 스타일 CRUD
 * QueryBuilder를 통한 Fluent API 제공
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
     * QueryBuilder 인스턴스 생성
     * @param string $alias 테이블 별칭 (예: 't')
     */
    public function query(string $alias = ''): QueryBuilder
    {
        return new QueryBuilder($this->table, $alias, $this->db);
    }

    /**
     * PK로 단건 조회
     */
    public function findById($id)
    {
        return $this->query()
            ->where($this->primaryKey, $id)
            ->first();
    }

    /**
     * 단일 컬럼 조건으로 단건 조회
     */
    public function firstWhere(string $col, $value)
    {
        return $this->query()
            ->where($col, $value)
            ->first();
    }

    /**
     * 조건부 목록 조회
     */
    public function findAll(array $conditions = [], $orderBy = 'id DESC', $limit = 20, $offset = 0)
    {
        $qb = $this->query();

        foreach ($conditions as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (substr($key, 0, 1) === '%' && substr($key, -1) === '%') {
                $col = trim($key, '%');
                $qb->whereLike($col, $value);
            } else {
                $qb->where($key, $value);
            }
        }

        return $qb->orderBy($orderBy)
            ->limit((int)$limit)
            ->offset((int)$offset)
            ->get();
    }

    /**
     * 조건부 카운트
     */
    public function count(array $conditions = [])
    {
        $qb = $this->query();

        foreach ($conditions as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (substr($key, 0, 1) === '%' && substr($key, -1) === '%') {
                $col = trim($key, '%');
                $qb->whereLike($col, $value);
            } else {
                $qb->where($key, $value);
            }
        }

        return $qb->count();
    }

    /**
     * 삽입
     */
    public function insert(array $data)
    {
        return $this->query()->insert($data);
    }

    /**
     * 수정
     */
    public function update($id, array $data)
    {
        return $this->query()
            ->where($this->primaryKey, $id)
            ->update($data);
    }

    /**
     * 삭제
     */
    public function delete($id)
    {
        return $this->query()
            ->where($this->primaryKey, $id)
            ->delete();
    }
}
