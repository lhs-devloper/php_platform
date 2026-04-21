<?php
/**
 * 페이지네이션 헬퍼
 */
class Pagination
{
    private $totalItems;
    private $perPage;
    private $currentPage;
    private $totalPages;

    public function __construct($totalItems, $perPage, $currentPage)
    {
        $this->totalItems = (int)$totalItems;
        $this->perPage = max(1, (int)$perPage);
        $this->totalPages = max(1, (int)ceil($this->totalItems / $this->perPage));
        $this->currentPage = max(1, min((int)$currentPage, $this->totalPages));
    }

    public function offset()
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function currentPage()
    {
        return $this->currentPage;
    }

    public function totalPages()
    {
        return $this->totalPages;
    }

    public function totalItems()
    {
        return $this->totalItems;
    }

    public function hasPrev()
    {
        return $this->currentPage > 1;
    }

    public function hasNext()
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * 페이지네이션 HTML 생성
     * $baseUrl에 &page=N 을 추가
     */
    public function links($baseUrl)
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav><ul class="pagination pagination-sm justify-content-center">';

        // 이전
        if ($this->hasPrev()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($this->currentPage - 1) . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // 페이지 번호 (최대 5개)
        $start = max(1, $this->currentPage - 2);
        $end = min($this->totalPages, $start + 4);
        $start = max(1, $end - 4);

        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $this->currentPage ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }

        // 다음
        if ($this->hasNext()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($this->currentPage + 1) . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
