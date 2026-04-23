<?php
$t = $tenant;
$partner = $isPartner ?? false;
$allowed = $allowedServiceTypes ?? ['POSTURE', 'FOOT', 'BOTH'];
$serviceLabels = ['POSTURE' => 'AI자세분석', 'FOOT' => 'AIoT족부분석', 'BOTH' => '통합'];
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="index.php?route=<?= $isEdit ? 'tenant/edit&id=' . $t['id'] : 'tenant/create' ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">업체명 <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control"
                           value="<?= htmlspecialchars($t['company_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">사업자등록번호</label>
                    <input type="text" name="business_number" class="form-control"
                           value="<?= htmlspecialchars($t['business_number'] ?? '') ?>"
                           placeholder="000-00-00000">
                </div>

                <div class="col-md-4">
                    <label class="form-label">대표자명</label>
                    <input type="text" name="ceo_name" class="form-control"
                           value="<?= htmlspecialchars($t['ceo_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">전화번호</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($t['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">이메일</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($t['email'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">우편번호</label>
                    <input type="text" name="zipcode" class="form-control"
                           value="<?= htmlspecialchars($t['zipcode'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">주소</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= htmlspecialchars($t['address'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">상세주소</label>
                    <input type="text" name="address_detail" class="form-control"
                           value="<?= htmlspecialchars($t['address_detail'] ?? '') ?>">
                </div>

                <?php if ($partner && !$isEdit): ?>
                <!-- 협력업체: 상태 ACTIVE 고정 -->
                <input type="hidden" name="status" value="ACTIVE">
                <div class="col-md-3">
                    <label class="form-label">상태</label>
                    <input type="text" class="form-control bg-light text-success fw-semibold" value="즉시 활성화" disabled>
                </div>
                <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label">상태 <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <?php foreach (['PENDING' => '대기', 'ACTIVE' => '운영중', 'SUSPENDED' => '정지', 'TERMINATED' => '해지'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($t['status'] ?? 'PENDING') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">서비스 유형 <span class="text-danger">*</span></label>
                    <?php if ($partner && !$isEdit && count($allowed) === 1): ?>
                    <!-- 협력업체 서비스 유형이 단일인 경우 고정 -->
                    <input type="hidden" name="service_type" value="<?= $allowed[0] ?>">
                    <input type="text" class="form-control bg-light" value="<?= $serviceLabels[$allowed[0]] ?>" disabled>
                    <?php else: ?>
                    <select name="service_type" class="form-select" required>
                        <?php if (!$partner || $isEdit): ?>
                        <option value="BOTH" <?= ($t['service_type'] ?? 'BOTH') === 'BOTH' ? 'selected' : '' ?>>통합</option>
                        <option value="POSTURE" <?= ($t['service_type'] ?? '') === 'POSTURE' ? 'selected' : '' ?>>AI자세분석</option>
                        <option value="FOOT" <?= ($t['service_type'] ?? '') === 'FOOT' ? 'selected' : '' ?>>AIoT족부분석</option>
                        <?php else: ?>
                        <?php foreach ($allowed as $type): ?>
                        <option value="<?= $type ?>" <?= ($t['service_type'] ?? $allowed[0]) === $type ? 'selected' : '' ?>><?= $serviceLabels[$type] ?></option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">계약 시작일</label>
                    <input type="date" name="contract_start" class="form-control"
                           value="<?= htmlspecialchars($t['contract_start'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">계약 종료일</label>
                    <input type="date" name="contract_end" class="form-control"
                           value="<?= htmlspecialchars($t['contract_end'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <div class="card bg-light border">
                        <div class="card-body py-3">
                            <label class="form-label fw-semibold mb-2">
                                <i class="bi bi-key"></i> 앱 로그인 계정 <?= $isEdit ? '(초기 발급값)' : '' ?>
                            </label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">앱 아이디</label>
                                    <input type="text" name="app_id" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($t['app_id'] ?? '') ?>"
                                           placeholder="admin" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">
                                        앱 비밀번호
                                        <?php if ($isEdit): ?><span class="text-muted">(변경 시에만 입력)</span><?php endif; ?>
                                    </label>
                                    <input type="text" name="app_pw" class="form-control form-control-sm"
                                           value="" autocomplete="off"
                                           placeholder="<?= $isEdit ? '비워두면 기존 값 유지' : '초기 비밀번호' ?>">
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                가맹점 앱의 초기 관리자 계정입니다. 발급 후 가맹점 관리자페이지에서 자유롭게 변경할 수 있으며,
                                변경 이후에는 이곳의 값은 최신 상태가 아닐 수 있습니다.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">메모</label>
                    <textarea name="memo" class="form-control" rows="3"><?= htmlspecialchars($t['memo'] ?? '') ?></textarea>
                </div>

                <?php if (!$isEdit && $partner): ?>
                <!-- 협력업체: 슬러그 입력 (자동 프로비저닝 필수) -->
                <div class="col-12">
                    <div class="card bg-light border">
                        <div class="card-body py-3">
                            <label class="form-label fw-semibold mb-2">
                                <i class="bi bi-globe"></i> 사이트 주소 설정 <span class="text-danger">*</span>
                            </label>
                            <div class="row align-items-end g-2">
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="slug" class="form-control" required
                                               placeholder="myshop" pattern="[a-z0-9\-]+"
                                               value="<?= htmlspecialchars($t['slug'] ?? '') ?>">
                                        <span class="input-group-text"><?= PROVISION_DOMAIN_SUFFIX ?></span>
                                    </div>
                                    <small class="text-muted">영문 소문자, 숫자, 하이픈만 가능 (3~50자)</small>
                                </div>
                                <div class="col-md-8">
                                    <small class="text-muted">
                                        입력한 주소로 사이트가 자동 생성됩니다.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-lightning-charge-fill"></i>
                        등록 즉시 <strong>활성화</strong>되며, DB와 사이트가 자동 생성되어 바로 이용 가능합니다.
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$isEdit && !$partner): ?>
                <!-- DB 자동 프로비저닝 옵션 (중앙관리자 등록 시에만) -->
                <div class="col-12">
                    <div class="card bg-light border">
                        <div class="card-body py-3">
                            <div class="form-check">
                                <input type="checkbox" name="auto_provision" value="1" class="form-check-input"
                                       id="autoProvision" checked onchange="document.getElementById('slugGroup').style.display = this.checked ? '' : 'none'">
                                <label class="form-check-label fw-semibold" for="autoProvision">
                                    <i class="bi bi-database-add"></i> DB 자동 프로비저닝
                                </label>
                            </div>
                            <div id="slugGroup" class="mt-3 ms-4">
                                <div class="row align-items-end g-2">
                                    <div class="col-md-4">
                                        <label class="form-label mb-1 small fw-semibold">서브도메인 (슬러그) <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="slug" class="form-control"
                                                   placeholder="smartidea" pattern="[a-z0-9\-]+"
                                                   value="<?= htmlspecialchars($t['slug'] ?? '') ?>">
                                            <span class="input-group-text"><?= PROVISION_DOMAIN_SUFFIX ?></span>
                                        </div>
                                        <small class="text-muted">영문 소문자, 숫자, 하이픈만 가능 (3~50자)</small>
                                    </div>
                                    <div class="col-md-8">
                                        <small class="text-muted">
                                            자동 수행: DB 생성 (<code>{슬러그}</code>) + 스키마 적용 + 도메인 매핑 (<code>{슬러그}<?= PROVISION_DOMAIN_SUFFIX ?></code>)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? '수정' : '등록' ?>
                </button>
                <a href="index.php?route=tenant/list" class="btn btn-outline-secondary">취소</a>
            </div>
        </form>
    </div>
</div>
