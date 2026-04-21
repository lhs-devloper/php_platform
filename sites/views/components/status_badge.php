<?php
function statusBadge($status) {
    $map = [
        'ACTIVE'    => ['bg-success', '수강'],
        'PAUSED'    => ['bg-warning text-dark', '휴원'],
        'HONORARY'  => ['bg-purple', '명예'],
        'WITHDRAWN' => ['bg-secondary', '퇴원'],
        'COMPLETED' => ['bg-success', '완료'],
        'WAIT'      => ['bg-warning text-dark', '대기'],
        'PROCESSING'=> ['bg-info text-dark', '처리중'],
        'FAILED'    => ['bg-danger', '실패'],
        'OK'        => ['bg-success', '완료'],
        'ERROR'     => ['bg-danger', '오류'],
    ];
    if (isset($map[$status])) {
        return '<span class="badge ' . $map[$status][0] . '">' . $map[$status][1] . '</span>';
    }
    return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
}

function genderBadge($gender) {
    if ($gender === 'M') return '<span class="badge bg-primary">남</span>';
    if ($gender === 'F') return '<span class="badge bg-danger">여</span>';
    return '<span class="badge bg-light text-dark">-</span>';
}

/**
 * 자세분석 이미지 URL 생성
 */
function postureImgUrl($path) {
    if (!$path) return '';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    return rtrim(IMG_POSTURE_BASE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * 족부분석 이미지 URL 생성
 */
function footImgUrl($path) {
    if (!$path) return '';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    return rtrim(IMG_FOOT_BASE_URL, '/') . '/' . ltrim($path, '/');
}

function roleBadge($role) {
    $map = [
        'SUPER' => ['bg-danger', '최고관리자'],
        'ADMIN' => ['bg-primary', '관리자'],
        'STAFF' => ['bg-secondary', '직원'],
    ];
    if (isset($map[$role])) {
        return '<span class="badge ' . $map[$role][0] . '">' . $map[$role][1] . '</span>';
    }
    return '<span class="badge bg-light text-dark">' . htmlspecialchars($role) . '</span>';
}
