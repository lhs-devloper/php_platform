<?php
class TenantModel extends Model
{
    protected $table = 'tenant';

    /**
     * 가맹점 검색 (키워드, 상태, 서비스유형, 협력업체 소속 필터)
     */
    public function search($keyword = '', $status = '', $serviceType = '', $page = 1, $perPage = 20, array $tenantIds = [])
    {
        return $this->query('t')
            ->select("t.*, td.domain AS site_domain")
            ->leftJoin("tenant_database td", "td.tenant_id = t.id AND td.status = 'ACTIVE'")
            ->when(!empty($tenantIds), function ($q) use ($tenantIds) {
                $q->whereIn('t.id', $tenantIds);
            })
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->whereMultiLike(['t.company_name', 't.business_number', 't.ceo_name'], $keyword);
            })
            ->when($status !== '', function ($q) use ($status) {
                $q->whereColumn('t.status', $status);
            })
            ->when($serviceType !== '', function ($q) use ($serviceType) {
                $q->whereColumn('t.service_type', $serviceType);
            })
            ->orderBy('t.created_at DESC')
            ->paginate($page, $perPage);
    }

    /**
     * 상태별 카운트
     */
    public function countByStatus()
    {
        $rows = $this->query()
            ->selectRaw('status, COUNT(*) AS cnt')
            ->groupBy('status')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * 활성 가맹점 목록 (TERMINATED 제외)
     */
    public function getActiveList()
    {
        return $this->query()
            ->select('id, company_name, status')
            ->whereColumn('status', '!=', 'TERMINATED')
            ->orderBy('company_name ASC')
            ->get();
    }

    /**
     * 특정 ID 목록에 해당하는 가맹점 상태 통계
     */
    public function getStatsByIds(array $ids): array
    {
        $stats = ['total' => 0, 'ACTIVE' => 0, 'PENDING' => 0, 'SUSPENDED' => 0];
        if (empty($ids)) {
            return $stats;
        }

        $rows = $this->query()
            ->selectRaw('status, COUNT(*) AS cnt')
            ->whereIn('id', $ids)
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $stats[$row['status']] = (int)$row['cnt'];
            $stats['total'] += (int)$row['cnt'];
        }
        return $stats;
    }

    /**
     * 특정 ID 목록에 해당하는 가맹점 + 도메인 정보
     */
    public function findByIdsWithDomain(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->query('t')
            ->select("t.*, td.domain AS site_domain")
            ->leftJoin("tenant_database td", "td.tenant_id = t.id AND td.status = 'ACTIVE'")
            ->whereIn('t.id', $ids)
            ->orderBy('t.company_name ASC')
            ->get();
    }

    /**
     * 가맹점 완전 삭제 시 종속 테이블 데이터 일괄 삭제
     */
    public function destroyDependents(int $id): void
    {
        $dependentTables = [
            'provision_log',
            'partner_access_log',
            'partner_access_request',
            'partner_tenant',
            'usage_daily',
            'payment',
            'subscription',
            'inquiry',
            'notice',
            'tenant_contact',
            'tenant_database',
        ];

        foreach ($dependentTables as $table) {
            $col = ($table === 'partner_access_request') ? 'requested_tenant_id' : 'tenant_id';
            (new QueryBuilder($table))
                ->where($col, $id)
                ->delete();
        }
    }
}
