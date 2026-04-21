<?php
/**
 * 상태값에 따른 Bootstrap 뱃지 반환
 */
function statusBadge($status) {
    $map = [
        // 공통
        'ACTIVE'       => ['bg-success', '운영중'],
        'PENDING'      => ['bg-warning text-dark', '대기'],
        'SUSPENDED'    => ['bg-danger', '정지'],
        'TERMINATED'   => ['bg-secondary', '해지'],
        // 구독
        'TRIAL'        => ['bg-info text-dark', '체험'],
        'EXPIRED'      => ['bg-secondary', '만료'],
        'CANCELLED'    => ['bg-dark', '취소'],
        // 열람요청
        'APPROVED'     => ['bg-success', '승인'],
        'REJECTED'     => ['bg-danger', '거절'],
        'REVOKED'      => ['bg-dark', '철회'],
        // DB
        'MAINTENANCE'  => ['bg-warning text-dark', '점검'],
        'DECOMMISSIONED' => ['bg-secondary', '폐기'],
    ];

    if (isset($map[$status])) {
        return '<span class="badge ' . $map[$status][0] . '">' . $map[$status][1] . '</span>';
    }
    return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
}

function serviceTypeBadge($type) {
    $map = [
        'POSTURE' => ['bg-primary', 'AI자세분석'],
        'FOOT'    => ['bg-info text-dark', 'AIoT족부분석'],
        'BOTH'    => ['bg-dark', '통합'],
    ];
    // Modernized colors for Admin portal consistency
    if ($type === 'POSTURE') $map['POSTURE'] = ['bg-primary', 'AI자세분석'];
    if ($type === 'FOOT') $map['FOOT'] = ['bg-success', 'AIoT족부분석']; // Teal-ish success color
    if (isset($map[$type])) {
        return '<span class="badge ' . $map[$type][0] . '">' . $map[$type][1] . '</span>';
    }
    return '<span class="badge bg-light text-dark">' . htmlspecialchars($type) . '</span>';
}

function roleBadge($role) {
    $map = [
        'SUPER_ADMIN'    => ['bg-danger', '최고관리자'],
        'ADMIN'          => ['bg-primary', '관리자'],
        'OPERATOR'       => ['bg-success', '운영자'],
        'VIEWER'         => ['bg-secondary', '조회전용'],
        'PARTNER_ADMIN'  => ['bg-info text-dark', '협력업체 관리자'],
        'PARTNER_STAFF'  => ['bg-info text-dark', '협력업체 직원'],
        'PARTNER_VIEWER' => ['bg-secondary', '협력업체 조회'],
    ];
    if (isset($map[$role])) {
        return '<span class="badge ' . $map[$role][0] . '">' . $map[$role][1] . '</span>';
    }
    return '<span class="badge bg-light text-dark">' . htmlspecialchars($role) . '</span>';
}
