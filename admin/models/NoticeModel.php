<?php
class NoticeModel extends Model
{
    protected $table = 'notice';

    /**
     * 공지 검색
     */
    public function search($keyword = '', $targetType = '', $isPublished = '', $page = 1, $perPage = 20)
    {
        return $this->query('n')
            ->select('n.*, ca.name AS admin_name')
            ->selectRaw("(SELECT GROUP_CONCAT(t2.company_name SEPARATOR ', ')
                          FROM notice_tenant nt2
                          JOIN tenant t2 ON t2.id = nt2.tenant_id
                          WHERE nt2.notice_id = n.id) AS tenant_names")
            ->leftJoin('central_admin ca', 'ca.id = n.admin_id')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->whereMultiLike(['n.title', 'n.content'], $keyword);
            })
            ->when($targetType !== '', function ($q) use ($targetType) {
                $q->whereColumn('n.target_type', $targetType);
            })
            ->when($isPublished !== '', function ($q) use ($isPublished) {
                $q->whereColumn('n.is_published', (int)$isPublished);
            })
            ->orderBy('n.is_pinned DESC, n.created_at DESC')
            ->paginate($page, $perPage);
    }

    /**
     * 상세 조회 (관리자명 + 가맹점명 포함)
     */
    public function findByIdWithRelations($id)
    {
        return $this->query('n')
            ->select('n.*, ca.name AS admin_name')
            ->selectRaw("(SELECT GROUP_CONCAT(t2.company_name SEPARATOR ', ')
                          FROM notice_tenant nt2
                          JOIN tenant t2 ON t2.id = nt2.tenant_id
                          WHERE nt2.notice_id = n.id) AS tenant_names")
            ->leftJoin('central_admin ca', 'ca.id = n.admin_id')
            ->whereColumn('n.id', $id)
            ->first();
    }

    /**
     * 공지에 연결된 가맹점 ID 목록
     */
    public function getTenantIds(int $noticeId): array
    {
        return (new QueryBuilder('notice_tenant'))
            ->where('notice_id', $noticeId)
            ->pluck('tenant_id');
    }

    /**
     * 공지-가맹점 매핑 저장 (기존 삭제 후 재삽입)
     */
    public function saveTenantIds(int $noticeId, array $tenantIds): void
    {
        (new QueryBuilder('notice_tenant'))
            ->where('notice_id', $noticeId)
            ->delete();

        if (!empty($tenantIds)) {
            $qb = new QueryBuilder('notice_tenant');
            foreach ($tenantIds as $tid) {
                $tid = (int)$tid;
                if ($tid > 0) {
                    $qb->insert(['notice_id' => $noticeId, 'tenant_id' => $tid]);
                }
            }
        }
    }

    /**
     * 특정 가맹점이 볼 수 있는 공지 목록
     */
    public function getForTenant($tenantId, $page = 1, $perPage = 20)
    {
        return $this->query('n')
            ->select('n.*')
            ->whereColumn('n.is_published', 1)
            ->whereRaw("(n.target_type = 'ALL'
                OR (n.target_type = 'SPECIFIC' AND EXISTS (
                    SELECT 1 FROM notice_tenant nt WHERE nt.notice_id = n.id AND nt.tenant_id = ?
                )))", [$tenantId])
            ->orderBy('n.is_pinned DESC, n.published_at DESC')
            ->paginate($page, $perPage);
    }
}
