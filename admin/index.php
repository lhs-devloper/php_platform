<?php

/**
 * CentralAdmin - 프론트 컨트롤러
 */

// 설정 로드
require_once __DIR__ . '/config.php';

// ----------------------------------------------------------
// DB 설치 상태 점검 (설치 안 되어 있으면 설치 화면으로)
// ----------------------------------------------------------
require_once BASE_PATH . '/core/InstallChecker.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';

// 설치 요청 처리 (POST)
if ($route === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $installResult = InstallChecker::install(
        $_POST['db_host'] ?? DB_HOST,
        $_POST['db_port'] ?? DB_PORT,
        $_POST['db_user'] ?? DB_USER,
        $_POST['db_pass'] ?? DB_PASS,
        $_POST['db_name'] ?? DB_NAME
    );

    // .env 파일 업데이트 (입력값이 다르면)
    $postDbHost = $_POST['db_host'] ?? DB_HOST;
    $postDbPort = $_POST['db_port'] ?? DB_PORT;
    $postDbUser = $_POST['db_user'] ?? DB_USER;
    $postDbPass = $_POST['db_pass'] ?? DB_PASS;
    $postDbName = $_POST['db_name'] ?? DB_NAME;

    if ($postDbHost !== DB_HOST || (int)$postDbPort !== DB_PORT || $postDbUser !== DB_USER || $postDbPass !== DB_PASS || $postDbName !== DB_NAME) {
        $envPath = BASE_PATH . '/.env';
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

        $updates = [
            'DB_HOST' => $postDbHost,
            'DB_PORT' => (string)(int)$postDbPort,
            'DB_NAME' => $postDbName,
            'DB_USER' => $postDbUser,
            'DB_PASS' => $postDbPass,
        ];

        foreach ($updates as $key => $val) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$val}", $envContent);
            } else {
                $envContent .= "\n{$key}={$val}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    if ($installResult['success']) {
        $installSuccess = '데이터베이스가 성공적으로 설치되었습니다!';
    } else {
        $installError = $installResult['error'];
    }

    $checkResult = InstallChecker::check();
    include BASE_PATH . '/views/install.php';
    exit;
}

// DB 설치 상태 점검
$checkResult = InstallChecker::check();

if (!$checkResult['installed']) {
    // 설치가 안 되어 있으면 설치 화면 표시
    $installError = $checkResult['error'];
    include BASE_PATH . '/views/install.php';
    exit;
}

// ----------------------------------------------------------
// DB 설치 완료 → 정상 앱 로드
// ----------------------------------------------------------

// 코어 클래스 로드
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/QueryBuilder.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Router.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Validator.php';
require_once BASE_PATH . '/core/Pagination.php';
require_once BASE_PATH . '/core/ProvisionService.php';
require_once BASE_PATH . '/core/MigrationService.php';
require_once BASE_PATH . '/core/SchemaUpdateService.php';
require_once BASE_PATH . '/core/EmailService.php';

// 뷰 컴포넌트 함수 로드
require_once BASE_PATH . '/views/components/status_badge.php';

// 모델 로드
require_once BASE_PATH . '/models/AdminModel.php';
require_once BASE_PATH . '/models/AuditLogModel.php';
require_once BASE_PATH . '/models/DashboardModel.php';
require_once BASE_PATH . '/models/TenantModel.php';
require_once BASE_PATH . '/models/TenantContactModel.php';
require_once BASE_PATH . '/models/TenantDatabaseModel.php';
require_once BASE_PATH . '/models/PartnerModel.php';
require_once BASE_PATH . '/models/PartnerAdminModel.php';
require_once BASE_PATH . '/models/PartnerTenantModel.php';
require_once BASE_PATH . '/models/AccessRequestModel.php';
require_once BASE_PATH . '/models/NoticeModel.php';
require_once BASE_PATH . '/models/EmailLogModel.php';
require_once BASE_PATH . '/models/SystemConfigModel.php';

// 라우터 설정
$router = new Router();

// 인증
$router->add('auth/login', 'AuthController', 'login');
$router->add('auth/logout', 'AuthController', 'logout');

// 대시보드
$router->add('dashboard', 'DashboardController', 'index');

// 가맹점
$router->add('tenant/list', 'TenantController', 'list');
$router->add('tenant/create', 'TenantController', 'create');
$router->add('tenant/edit', 'TenantController', 'edit');
$router->add('tenant/detail', 'TenantController', 'detail');
$router->add('tenant/delete', 'TenantController', 'delete');
$router->add('tenant/provision', 'TenantController', 'provision');
$router->add('tenant/access_site', 'TenantController', 'accessSite');
$router->add('tenant/migrate', 'TenantController', 'migrate');
$router->add('tenant/destroy', 'TenantController', 'destroy');
$router->add('tenant/contact/save', 'TenantController', 'saveContact');
$router->add('tenant/contact/delete', 'TenantController', 'deleteContact');

// 협력업체
$router->add('partner/list', 'PartnerController', 'list');
$router->add('partner/create', 'PartnerController', 'create');
$router->add('partner/edit', 'PartnerController', 'edit');
$router->add('partner/detail', 'PartnerController', 'detail');
$router->add('partner/delete', 'PartnerController', 'delete');
$router->add('partner/admin/save', 'PartnerController', 'saveAdmin');
$router->add('partner/admin/toggle', 'PartnerController', 'toggleAdmin');
$router->add('partner/admin/delete', 'PartnerController', 'deleteAdmin');
$router->add('partner/tenant/add', 'PartnerController', 'addTenant');
$router->add('partner/tenant/remove', 'PartnerController', 'removeTenant');

// 열람 요청
$router->add('access_request/list', 'AccessRequestController', 'list');
$router->add('access_request/create', 'AccessRequestController', 'create');
$router->add('access_request/detail', 'AccessRequestController', 'detail');
$router->add('access_request/approve', 'AccessRequestController', 'approve');
$router->add('access_request/reject', 'AccessRequestController', 'reject');
$router->add('access_request/revoke', 'AccessRequestController', 'revoke');

// 스키마 업데이트
$router->add('schema/list', 'SchemaController', 'list');
$router->add('schema/execute', 'SchemaController', 'execute');
$router->add('schema/execute_all', 'SchemaController', 'executeAll');

// 공지사항
$router->add('notice/list', 'NoticeController', 'list');
$router->add('notice/create', 'NoticeController', 'create');
$router->add('notice/edit', 'NoticeController', 'edit');
$router->add('notice/detail', 'NoticeController', 'detail');
$router->add('notice/delete', 'NoticeController', 'delete');
$router->add('notice/toggle_publish', 'NoticeController', 'togglePublish');
$router->add('notice/upload_image', 'NoticeController', 'uploadImage');

// 이메일
$router->add('email/list', 'EmailController', 'list');
$router->add('email/compose', 'EmailController', 'compose');
$router->add('email/detail', 'EmailController', 'detail');
$router->add('email/resend', 'EmailController', 'resend');
$router->add('email/delete', 'EmailController', 'delete');
$router->add('email/settings', 'EmailController', 'settings');
$router->add('email/test_smtp', 'EmailController', 'testSmtp');

// 라우트 결정 (설치 체크 시 이미 읽었으므로 기본값만 보완)
if (empty($route)) {
    $route = 'dashboard';
}

// 인증 체크 (로그인 페이지 제외)
if ($route !== 'auth/login' && !Auth::check()) {
    header('Location: index.php?route=auth/login');
    exit;
}

// 협력업체 접근 제한: 허용된 라우트만 접근 가능
if (Auth::isPartner()) {
    $partnerAllowed = [
        'dashboard',
        'auth/login',
        'auth/logout',
        'tenant/list',
        'tenant/create',
        'tenant/detail',
        'tenant/access_site',
        'notice/list',
        'notice/detail',
        'access_request/list',
        'access_request/create',
        'access_request/detail',
    ];
    if (!in_array($route, $partnerAllowed)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => '접근 권한이 없습니다.'];
        header('Location: index.php?route=dashboard');
        exit;
    }
}

// 디스패치
$router->dispatch($route);
