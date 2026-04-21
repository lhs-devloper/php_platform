<?php
/**
 * 가맹점 사이트 - 프론트 컨트롤러
 */

require_once __DIR__ . '/config.php';

// Core
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Router.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Validator.php';
require_once BASE_PATH . '/core/Pagination.php';

// Components
require_once BASE_PATH . '/views/components/status_badge.php';

// Models
require_once BASE_PATH . '/models/AdminModel.php';
require_once BASE_PATH . '/models/DashboardModel.php';
require_once BASE_PATH . '/models/MemberModel.php';
require_once BASE_PATH . '/models/InstructorModel.php';
require_once BASE_PATH . '/models/ClassCodeModel.php';
require_once BASE_PATH . '/models/PostureSessionModel.php';
require_once BASE_PATH . '/models/PostureReportModel.php';
require_once BASE_PATH . '/models/FootSessionModel.php';
require_once BASE_PATH . '/models/FootReportModel.php';
require_once BASE_PATH . '/models/ConsultationModel.php';
require_once BASE_PATH . '/models/AiConfigModel.php';
require_once BASE_PATH . '/models/AiUsageLogModel.php';
require_once BASE_PATH . '/models/NoticeModel.php';

// Theme
require_once BASE_PATH . '/core/ThemeManager.php';
require_once BASE_PATH . '/models/ThemeSettingsModel.php';
require_once BASE_PATH . '/models/ThemeViewOverrideModel.php';

// AI
require_once BASE_PATH . '/core/AiClient.php';

// Theme 초기화 (라우팅 전)
ThemeManager::init();

// Router
$router = new Router();

$router->add('auth/login', 'AuthController', 'login');
$router->add('auth/logout', 'AuthController', 'logout');
$router->add('auth/admin_access', 'AuthController', 'adminAccess');
$router->add('dashboard', 'DashboardController', 'index');

$router->add('member/list', 'MemberController', 'list');
$router->add('member/create', 'MemberController', 'create');
$router->add('member/edit', 'MemberController', 'edit');
$router->add('member/detail', 'MemberController', 'detail');
$router->add('member/delete', 'MemberController', 'delete');
$router->add('member/toggle_consultation', 'MemberController', 'toggleConsultation');

$router->add('class_code/list', 'ClassCodeController', 'index');
$router->add('class_code/create', 'ClassCodeController', 'create');
$router->add('class_code/edit', 'ClassCodeController', 'edit');
$router->add('class_code/toggle', 'ClassCodeController', 'toggle');
$router->add('class_code/delete', 'ClassCodeController', 'delete');

$router->add('posture/report', 'PostureController', 'report');
$router->add('posture/compare', 'PostureController', 'compare');
$router->add('foot/report', 'FootController', 'report');
$router->add('foot/compare', 'FootController', 'compare');

$router->add('consultation/generate', 'ConsultationController', 'generate');
$router->add('consultation/store', 'ConsultationController', 'store');
$router->add('consultation/detail', 'ConsultationController', 'detail');
$router->add('consultation/preview_report', 'ConsultationController', 'previewReport');

$router->add('notice/list', 'NoticeController', 'list');
$router->add('notice/detail', 'NoticeController', 'detail');

$router->add('lab', 'LabController', 'index');

// Theme
$router->add('theme/settings', 'ThemeController', 'settings');
$router->add('theme/save_branding', 'ThemeController', 'saveBranding');
$router->add('theme/save_colors', 'ThemeController', 'saveColors');
$router->add('theme/save_fonts', 'ThemeController', 'saveFonts');
$router->add('theme/layout', 'ThemeController', 'layout');
$router->add('theme/save_layout', 'ThemeController', 'saveLayout');
$router->add('theme/templates', 'ThemeController', 'templates');
$router->add('theme/template_edit', 'ThemeController', 'templateEdit');
$router->add('theme/template_save', 'ThemeController', 'templateSave');
$router->add('theme/template_toggle', 'ThemeController', 'templateToggle');
$router->add('theme/template_delete', 'ThemeController', 'templateDelete');
$router->add('theme/preview', 'ThemeController', 'preview');
$router->add('theme/upload_logo', 'ThemeController', 'uploadLogo');
$router->add('theme/reset', 'ThemeController', 'reset');

// Dispatch
$route = isset($_GET['route']) ? $_GET['route'] : 'dashboard';

if ($route !== 'auth/login' && $route !== 'auth/admin_access' && !Auth::check()) {
    header('Location: index.php?route=auth/login');
    exit;
}

$router->dispatch($route);
