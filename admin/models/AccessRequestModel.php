<?php
class AccessRequestModel extends Model
{
    protected $table = 'partner_access_request';

    /**
     * 기본 JOIN 쿼리 빌더 (공통 4-table JOIN)
     */
    private function withDetails(): QueryBuilder
    {
        return $this->query('par')
            ->select('par.*,
                       p.company_name AS partner_name,
                       t.company_name AS tenant_name,
                       pa.name AS requester_name,
                       ca.name AS approver_name')
            ->leftJoin('partner p', 'p.id = par.partner_id')
            ->leftJoin('tenant t', 't.id = par.requested_tenant_id')
            ->leftJoin('partner_admin pa', 'pa.id = par.requester_admin_id')
            ->leftJoin('central_admin ca', 'ca.id = par.approved_by');
    }

    /**
     * 전체 열람 요청 목록 (중앙관리자용)
     */
    public function findAllWithDetails($status = '', $page = 1, $perPage = 20)
    {
        return $this->withDetails()
            ->when($status !== '', function ($q) use ($status) {
                $q->whereColumn('par.status', $status);
            })
            ->orderBy('par.created_at DESC')
            ->paginate($page, $perPage);
    }

    /**
     * 상세 조회 (4-table JOIN)
     */
    public function findByIdWithDetails($id)
    {
        return $this->query('par')
            ->select('par.*,
                       p.company_name AS partner_name, p.phone AS partner_phone,
                       t.company_name AS tenant_name, t.status AS tenant_status,
                       pa.name AS requester_name, pa.login_id AS requester_login_id,
                       ca.name AS approver_name')
            ->leftJoin('partner p', 'p.id = par.partner_id')
            ->leftJoin('tenant t', 't.id = par.requested_tenant_id')
            ->leftJoin('partner_admin pa', 'pa.id = par.requester_admin_id')
            ->leftJoin('central_admin ca', 'ca.id = par.approved_by')
            ->whereColumn('par.id', $id)
            ->first();
    }

    /**
     * 승인
     */
    public function approve($id, $approvedBy, $start, $end)
    {
        return $this->query()
            ->where('id', $id)
            ->updateRaw(
                "status = 'APPROVED', approved_by = ?, access_start = ?, access_end = ?, processed_at = NOW()",
                [$approvedBy, $start, $end]
            );
    }

    /**
     * 거절
     */
    public function reject($id, $approvedBy, $reason)
    {
        return $this->query()
            ->where('id', $id)
            ->updateRaw(
                "status = 'REJECTED', approved_by = ?, reject_reason = ?, processed_at = NOW()",
                [$approvedBy, $reason]
            );
    }

    /**
     * 철회
     */
    public function revoke($id)
    {
        return $this->query()
            ->where('id', $id)
            ->updateRaw("status = 'REVOKED', processed_at = NOW()");
    }

    /**
     * 대기 중 요청 수
     */
    public function countPending()
    {
        return $this->query()
            ->where('status', 'PENDING')
            ->count();
    }

    /**
     * 협력업체별 열람 요청 목록
     */
    public function findByPartnerId(int $partnerId, string $status = '', int $page = 1, int $perPage = 20): array
    {
        return $this->withDetails()
            ->whereColumn('par.partner_id', $partnerId)
            ->when($status !== '', function ($q) use ($status) {
                $q->whereColumn('par.status', $status);
            })
            ->orderBy('par.created_at DESC')
            ->paginate($page, $perPage);
    }
}
