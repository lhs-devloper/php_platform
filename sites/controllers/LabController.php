<?php
class LabController extends Controller
{
    /**
     * 업데이트 예정사항 목록
     * 새 항목 추가 시 이 배열에 항목을 추가하면 됨
     */
    private function getUpcomingFeatures()
    {
        return [
            [
                'id'          => 'trend_analysis',
                'icon'        => 'bi-graph-up-arrow',
                'title'       => '트렌드 분석 차트',
                'description' => '회원별 분석 히스토리를 시계열 차트로 시각화하여 개선 추이를 한눈에 파악합니다.',
                'status'      => 'coming',
                'version'     => 'v2.1',
                'category'    => '분석',
            ],
            [
                'id'          => 'bulk_report',
                'icon'        => 'bi-file-earmark-zip',
                'title'       => '리포트 일괄 다운로드',
                'description' => '선택한 회원들의 리포트를 PDF로 일괄 생성하여 ZIP 파일로 다운로드합니다.',
                'status'      => 'coming',
                'version'     => 'v2.2',
                'category'    => '리포트',
            ],
            [
                'id'          => 'member_app_link',
                'icon'        => 'bi-phone',
                'title'       => '회원 앱 연동',
                'description' => '회원이 모바일 앱에서 직접 자신의 리포트를 확인하고 운동 가이드를 받을 수 있습니다.',
                'status'      => 'coming',
                'version'     => 'v2.2',
                'category'    => '연동',
            ],
            [
                'id'          => 'smart_notification',
                'icon'        => 'bi-bell',
                'title'       => '스마트 알림',
                'description' => '분석 완료, 상담 예정일, 회원 재방문 주기 등 주요 이벤트를 자동으로 알려드립니다.',
                'status'      => 'coming',
                'version'     => 'v2.3',
                'category'    => '알림',
            ],
        ];
    }

    public function index()
    {
        $this->requireAuth();

        $features = $this->getUpcomingFeatures();

        // 카테고리별 그룹핑
        $categories = [];
        foreach ($features as $f) {
            $categories[$f['category']][] = $f;
        }

        $this->view('lab/index', [
            'pageTitle'    => '업데이트 예정사항',
            'features'     => $features,
            'categories'   => $categories,
            'totalCount'   => count($features),
        ]);
    }
}
