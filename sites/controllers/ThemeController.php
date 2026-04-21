<?php
/**
 * ThemeController - 테마/디자인 설정 관리
 * Level 1: 색상/브랜딩, Level 2: 레이아웃, Level 3: 템플릿 오버라이드
 */
class ThemeController extends Controller
{
    // ─── Level 1: 브랜딩/색상/폰트 설정 ───

    /**
     * 테마 설정 메인 페이지 (Level 1)
     */
    public function settings()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        $model = new ThemeSettingsModel();
        $allSettings = $model->getAllGrouped();

        // 기본 색상값 (CSS :root 변수 기본값)
        $defaultColors = [
            'accent'       => '#0d9488',
            'accent_dark'  => '#0f766e',
            'accent_light' => '#14b8a6',
            'accent_subtle'=> '#f0fdfa',
            'primary'      => '#0d9488',
            'success'      => '#10b981',
            'info'         => '#0ea5e9',
            'warning'      => '#f59e0b',
            'danger'       => '#ef4444',
            'dark'         => '#1e293b',
            'body_bg'      => '#f8fafc',
        ];

        // 색상 라벨 (한국어)
        $colorLabels = [
            'accent'       => '메인 강조색',
            'accent_dark'  => '메인 강조색 (진)',
            'accent_light' => '메인 강조색 (연)',
            'accent_subtle'=> '메인 강조 배경',
            'primary'      => '프라이머리',
            'success'      => '성공 (초록)',
            'info'         => '정보 (파랑)',
            'warning'      => '경고 (노랑)',
            'danger'       => '위험 (빨강)',
            'dark'         => '사이드바 배경',
            'body_bg'      => '페이지 배경색',
        ];

        // 현재 저장된 색상값 (없으면 기본값)
        $currentColors = [];
        foreach ($defaultColors as $key => $defaultVal) {
            $saved = isset($allSettings['colors'][$key]) ? $allSettings['colors'][$key]['setting_value'] : '';
            $currentColors[$key] = ($saved !== '') ? $saved : $defaultVal;
        }

        // 브랜딩 설정
        $branding = [
            'logo_url'    => isset($allSettings['branding']['logo_url']) ? $allSettings['branding']['logo_url']['setting_value'] : '',
            'site_title'  => isset($allSettings['branding']['site_title']) ? $allSettings['branding']['site_title']['setting_value'] : '',
            'favicon_url' => isset($allSettings['branding']['favicon_url']) ? $allSettings['branding']['favicon_url']['setting_value'] : '',
        ];

        // 폰트 설정
        $fonts = [
            'font_family' => isset($allSettings['fonts']['font_family']) ? $allSettings['fonts']['font_family']['setting_value'] : '',
            'font_url'    => isset($allSettings['fonts']['font_url']) ? $allSettings['fonts']['font_url']['setting_value'] : '',
        ];

        $this->view('theme/settings', [
            'pageTitle'     => '디자인 설정',
            'currentColors' => $currentColors,
            'defaultColors' => $defaultColors,
            'colorLabels'   => $colorLabels,
            'branding'      => $branding,
            'fonts'         => $fonts,
        ]);
    }

    /**
     * 브랜딩 저장
     */
    public function saveBranding()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $model = new ThemeSettingsModel();
        $adminId = Auth::user()['id'];

        $model->bulkUpsert('branding', [
            'site_title'  => $this->input('site_title', ''),
            'favicon_url' => $this->input('favicon_url', ''),
        ], $adminId);

        $this->flash('success', '브랜딩 설정이 저장되었습니다.');
        $this->redirect('theme/settings');
    }

    /**
     * 색상 저장
     */
    public function saveColors()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $model = new ThemeSettingsModel();
        $adminId = Auth::user()['id'];

        $allowedKeys = ['accent', 'accent_dark', 'accent_light', 'accent_subtle', 'primary',
                        'success', 'info', 'warning', 'danger', 'dark', 'body_bg'];

        $colors = [];
        foreach ($allowedKeys as $key) {
            $val = $this->input('color_' . $key, '');
            // hex 색상만 허용
            if ($val !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                $colors[$key] = $val;
            }
        }

        $model->bulkUpsert('colors', $colors, $adminId);

        $this->flash('success', '색상 설정이 저장되었습니다.');
        $this->redirect('theme/settings');
    }

    /**
     * 폰트 저장
     */
    public function saveFonts()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $model = new ThemeSettingsModel();
        $adminId = Auth::user()['id'];

        $model->bulkUpsert('fonts', [
            'font_family' => $this->input('font_family', ''),
            'font_url'    => $this->input('font_url', ''),
        ], $adminId);

        $this->flash('success', '폰트 설정이 저장되었습니다.');
        $this->redirect('theme/settings');
    }

    /**
     * 로고 업로드
     */
    public function uploadLogo()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('danger', '파일 업로드에 실패했습니다.');
            $this->redirect('theme/settings');
            return;
        }

        $file = $_FILES['logo'];

        // 파일 크기 제한 (500KB)
        if ($file['size'] > 512000) {
            $this->flash('danger', '파일 크기는 500KB 이하여야 합니다.');
            $this->redirect('theme/settings');
            return;
        }

        // MIME 타입 검증
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->flash('danger', '허용되지 않는 파일 형식입니다. (PNG, JPG, GIF, SVG, WebP만 가능)');
            $this->redirect('theme/settings');
            return;
        }

        // 업로드 디렉토리 생성
        $uploadDir = BASE_PATH . '/public/uploads/logos';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 랜덤 파일명
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) $ext = 'png';
        $filename = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->flash('danger', '파일 저장에 실패했습니다.');
            $this->redirect('theme/settings');
            return;
        }

        // 이전 로고 삭제
        $model = new ThemeSettingsModel();
        $oldLogo = ThemeManager::get('branding', 'logo_url', '');
        if ($oldLogo && file_exists(BASE_PATH . '/' . $oldLogo)) {
            @unlink(BASE_PATH . '/' . $oldLogo);
        }

        // DB 저장
        $relativePath = 'public/uploads/logos/' . $filename;
        $model->upsert('branding', 'logo_url', $relativePath, Auth::user()['id']);

        $this->flash('success', '로고가 업로드되었습니다.');
        $this->redirect('theme/settings');
    }

    // ─── Level 2: 레이아웃 설정 ───

    /**
     * 레이아웃 설정 페이지
     */
    public function layout()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        $this->view('theme/layout', [
            'pageTitle'        => '레이아웃 설정',
            'sidebarPosition'  => ThemeManager::get('layout', 'sidebar_position', 'left'),
            'layoutPreset'     => ThemeManager::get('layout', 'layout_preset', 'default'),
            'topbarStyle'      => ThemeManager::get('layout', 'topbar_style', 'default'),
        ]);
    }

    /**
     * 레이아웃 저장
     */
    public function saveLayout()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $model = new ThemeSettingsModel();
        $adminId = Auth::user()['id'];

        $sidebarPosition = $this->input('sidebar_position', 'left');
        if (!in_array($sidebarPosition, ['left', 'right'])) $sidebarPosition = 'left';

        $layoutPreset = $this->input('layout_preset', 'default');
        if (!in_array($layoutPreset, ['default', 'compact', 'wide'])) $layoutPreset = 'default';

        $topbarStyle = $this->input('topbar_style', 'default');
        if (!in_array($topbarStyle, ['default', 'colored', 'dark'])) $topbarStyle = 'default';

        $model->bulkUpsert('layout', [
            'sidebar_position' => $sidebarPosition,
            'layout_preset'    => $layoutPreset,
            'topbar_style'     => $topbarStyle,
        ], $adminId);

        $this->flash('success', '레이아웃 설정이 저장되었습니다.');
        $this->redirect('theme/layout');
    }

    // ─── Level 3: 템플릿 오버라이드 ───

    /**
     * 오버라이드 가능한 뷰 목록
     */
    public function templates()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        $overrideModel = new ThemeViewOverrideModel();
        $existingOverrides = $overrideModel->getAll();

        // 기존 오버라이드를 view_path 기준으로 맵핑
        $overrideMap = [];
        foreach ($existingOverrides as $o) {
            $overrideMap[$o['view_path']] = $o;
        }

        $overridableViews = ThemeManager::getOverridableViews();

        $this->view('theme/templates', [
            'pageTitle'        => '템플릿 오버라이드',
            'overridableViews' => $overridableViews,
            'overrideMap'      => $overrideMap,
        ]);
    }

    /**
     * 템플릿 에디터
     */
    public function templateEdit()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        $viewPath = $this->input('view_path', '');
        if (!in_array($viewPath, ThemeManager::getOverridableViews())) {
            $this->flash('danger', '오버라이드할 수 없는 뷰입니다.');
            $this->redirect('theme/templates');
            return;
        }

        $overrideModel = new ThemeViewOverrideModel();
        $existing = $overrideModel->getByViewPath($viewPath);

        // 기본 Mustache 템플릿 제공 (PHP 코드 미노출)
        $defaultSource = ThemeManager::getDefaultTemplate($viewPath);

        $variables = ThemeManager::getViewVariables($viewPath);

        $this->view('theme/template_edit', [
            'pageTitle'     => '템플릿 편집: ' . $viewPath,
            'viewPath'      => $viewPath,
            'existing'      => $existing,
            'defaultSource' => $defaultSource,
            'variables'     => $variables,
        ]);
    }

    /**
     * 템플릿 저장
     */
    public function templateSave()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $viewPath = $this->input('view_path', '');
        if (!in_array($viewPath, ThemeManager::getOverridableViews())) {
            $this->flash('danger', '오버라이드할 수 없는 뷰입니다.');
            $this->redirect('theme/templates');
            return;
        }

        $htmlContent = isset($_POST['html_content']) ? $_POST['html_content'] : '';
        $cssContent  = isset($_POST['css_content']) ? $_POST['css_content'] : '';

        // 보안 살균
        $htmlContent = ThemeManager::sanitizeTemplate($htmlContent);
        $cssContent  = ThemeManager::sanitizeCss($cssContent);

        $overrideModel = new ThemeViewOverrideModel();
        $overrideModel->upsert($viewPath, 'partial', $htmlContent, $cssContent, Auth::user()['id']);

        $this->flash('success', "템플릿이 저장되었습니다: {$viewPath}");
        $this->redirect('theme/template_edit', ['view_path' => $viewPath]);
    }

    /**
     * 템플릿 활성/비활성 토글
     */
    public function templateToggle()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id', 0);
        if ($id > 0) {
            $overrideModel = new ThemeViewOverrideModel();
            $overrideModel->toggleActive($id);
            $this->flash('success', '템플릿 상태가 변경되었습니다.');
        }
        $this->redirect('theme/templates');
    }

    /**
     * 템플릿 삭제 (기본 뷰로 복원)
     */
    public function templateDelete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $viewPath = $this->input('view_path', '');
        if ($viewPath) {
            $overrideModel = new ThemeViewOverrideModel();
            $overrideModel->deleteByViewPath($viewPath);
            $this->flash('success', "기본 템플릿으로 복원되었습니다: {$viewPath}");
        }
        $this->redirect('theme/templates');
    }

    // ─── 미리보기 ───

    /**
     * AJAX 미리보기 - 에디터의 템플릿을 렌더링하여 독립 HTML 반환
     */
    public function preview()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'POST만 허용'], 405);
            return;
        }

        $this->validateAjaxCsrf();

        $viewPath    = $this->input('view_path', '');
        $htmlContent = isset($_POST['html_content']) ? $_POST['html_content'] : '';
        $cssContent  = isset($_POST['css_content']) ? $_POST['css_content'] : '';

        if (!in_array($viewPath, ThemeManager::getOverridableViews())) {
            $this->json(['success' => false, 'message' => '허용되지 않는 뷰'], 400);
            return;
        }

        // 보안 살균
        $htmlContent = ThemeManager::sanitizeTemplate($htmlContent);
        $cssContent  = ThemeManager::sanitizeCss($cssContent);

        // 샘플 데이터로 렌더링
        $sampleData = ThemeManager::getSampleData($viewPath);

        try {
            $rendered = ThemeManager::renderTemplate($htmlContent, $sampleData);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => '렌더링 오류: ' . $e->getMessage()], 500);
            return;
        }

        // 테마 CSS 변수 포함한 독립 HTML 생성
        $themeVars = ThemeManager::cssVariables();

        $fullHtml = '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>미리보기</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    ' . ($themeVars ? '<style>' . $themeVars . '</style>' : '') . '
    ' . ($cssContent ? '<style>' . $cssContent . '</style>' : '') . '
</head>
<body style="background:var(--body-bg,#f8fafc);">
    <div class="p-4">
        ' . $rendered . '
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

        $this->json(['success' => true, 'html' => $fullHtml, 'newToken' => Auth::generateCsrfToken()]);
    }

    // ─── 공통 ───

    /**
     * 전체 테마 초기화
     */
    public function reset()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $model = new ThemeSettingsModel();
        $model->deleteGroup('colors');
        $model->deleteGroup('branding');
        $model->deleteGroup('fonts');
        $model->deleteGroup('layout');

        // 로고 파일 삭제
        $logoDir = BASE_PATH . '/public/uploads/logos';
        if (is_dir($logoDir)) {
            $files = glob($logoDir . '/*');
            foreach ($files as $f) {
                if (is_file($f)) @unlink($f);
            }
        }

        $this->flash('success', '모든 디자인 설정이 초기화되었습니다.');
        $this->redirect('theme/settings');
    }
}
