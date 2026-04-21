<?php
class PartnerModel extends Model
{
    protected $table = 'partner';

    public function search($keyword = '', $status = '', $page = 1, $perPage = 20)
    {
        return $this->query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->whereMultiLike(['company_name', 'business_number', 'ceo_name'], $keyword);
            })
            ->when($status !== '', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('created_at DESC')
            ->paginate($page, $perPage);
    }
}
