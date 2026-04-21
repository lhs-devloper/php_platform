<?php
/**
 * 이메일 발송 로그 모델
 */
class EmailLogModel extends Model
{
    protected $table = 'email_log';

    /**
     * 검색 (키워드, 상태 필터)
     */
    public function search(string $keyword = '', string $status = '', int $page = 1, int $perPage = 20): array
    {
        return $this->query('el')
            ->select('el.*, ca.name AS admin_name')
            ->leftJoin('central_admin ca', 'el.admin_id = ca.id')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->whereMultiLike(['el.subject', 'el.to_email'], $keyword);
            })
            ->when($status !== '', function ($q) use ($status) {
                $q->whereColumn('el.status', $status);
            })
            ->orderBy('el.id DESC')
            ->paginate($page, $perPage);
    }

    /**
     * 상세 조회 (관리자명 포함)
     */
    public function findByIdWithRelations(int $id): ?array
    {
        return $this->query('el')
            ->select('el.*, ca.name AS admin_name')
            ->leftJoin('central_admin ca', 'el.admin_id = ca.id')
            ->whereColumn('el.id', $id)
            ->first();
    }

    /**
     * 통계 (대시보드용)
     */
    public function getStats(): array
    {
        return $this->query()
            ->selectRaw("COUNT(*) AS total, SUM(status = 'SENT') AS sent, SUM(status = 'FAILED') AS failed, SUM(status = 'PENDING') AS pending")
            ->first();
    }
}
