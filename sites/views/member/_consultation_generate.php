<!-- AI 상담 소견 생성 모달 -->
<div class="modal fade" id="aiConsultModal" tabindex="-1" aria-labelledby="aiConsultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aiConsultModalLabel">
                    <i class="bi bi-robot"></i> AI 상담 소견 생성
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <!-- Step 1: 세션 선택 -->
                <div id="aiStep1">
                    <?php
                    $__svcType = defined('TENANT_SERVICE_TYPE') ? TENANT_SERVICE_TYPE : 'BOTH';
                    $__showPosture = in_array($__svcType, ['POSTURE', 'BOTH'], true);
                    $__showFoot    = in_array($__svcType, ['FOOT', 'BOTH'], true);
                    $__isBoth      = $__svcType === 'BOTH';
                    $__defaultType = $__showPosture ? 'POSTURE' : 'FOOT';
                    ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">분석 유형</label>
                        <div class="btn-group w-100" role="group">
                            <?php if ($__showPosture): ?>
                            <input type="radio" class="btn-check" name="ai_service_type" id="aiTypePosture" value="POSTURE" <?= $__defaultType === 'POSTURE' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="aiTypePosture"><i class="bi bi-body-text"></i> 자세분석</label>
                            <?php endif; ?>
                            <?php if ($__showFoot): ?>
                            <input type="radio" class="btn-check" name="ai_service_type" id="aiTypeFoot" value="FOOT" <?= $__defaultType === 'FOOT' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-info" for="aiTypeFoot"><i class="bi bi-footprints"></i> 족부분석</label>
                            <?php endif; ?>
                            <?php if ($__isBoth): ?>
                            <input type="radio" class="btn-check" name="ai_service_type" id="aiTypeBoth" value="BOTH">
                            <label class="btn btn-outline-success" for="aiTypeBoth"><i class="bi bi-layers"></i> 통합</label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 자세분석 세션 선택 -->
                    <div id="aiPostureSection">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">현재 자세분석 세션 <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm" id="aiCurrentPosture">
                                        <option value="">선택하세요</option>
                                        <?php foreach ($postureSessions as $s): ?>
                                            <?php if ($s['status'] === 'COMPLETED' && $s['has_report']): ?>
                                            <option value="<?= $s['id'] ?>"><?= substr($s['captured_at'], 0, 10) ?> (<?= $s['height'] ?>cm / <?= $s['weight'] ?>kg)</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary ai-preview-btn" data-type="posture" data-select="aiCurrentPosture" title="리포트 미리보기">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">비교 자세분석 세션 <small class="text-muted">(선택)</small></label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm" id="aiPrevPosture">
                                        <option value="">비교 안함</option>
                                        <?php foreach ($postureSessions as $s): ?>
                                            <?php if ($s['status'] === 'COMPLETED' && $s['has_report']): ?>
                                            <option value="<?= $s['id'] ?>"><?= substr($s['captured_at'], 0, 10) ?> (<?= $s['height'] ?>cm / <?= $s['weight'] ?>kg)</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary ai-preview-btn" data-type="posture" data-select="aiPrevPosture" title="리포트 미리보기">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 족부분석 세션 선택 -->
                    <div id="aiFootSection" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">현재 족부분석 세션 <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm" id="aiCurrentFoot">
                                        <option value="">선택하세요</option>
                                        <?php foreach ($footSessions as $s): ?>
                                            <?php if ($s['status'] === 'OK'): ?>
                                            <option value="<?= $s['id'] ?>"><?= substr($s['captured_at'], 0, 10) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary ai-preview-btn" data-type="foot" data-select="aiCurrentFoot" title="리포트 미리보기">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">비교 족부분석 세션 <small class="text-muted">(선택)</small></label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm" id="aiPrevFoot">
                                        <option value="">비교 안함</option>
                                        <?php foreach ($footSessions as $s): ?>
                                            <?php if ($s['status'] === 'OK'): ?>
                                            <option value="<?= $s['id'] ?>"><?= substr($s['captured_at'], 0, 10) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary ai-preview-btn" data-type="foot" data-select="aiPrevFoot" title="리포트 미리보기">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="aiError" class="alert alert-danger d-none" role="alert"></div>

                    <button type="button" class="btn btn-primary w-100" id="aiGenerateBtn">
                        <i class="bi bi-robot"></i> AI 소견 생성
                    </button>
                </div>

                <!-- Step 2: 로딩 -->
                <div id="aiStep2" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted">AI가 분석 데이터를 종합하고 있습니다...<br><small>(약 10~20초 소요)</small></p>
                </div>

                <!-- Step 3: 결과 미리보기 및 편집 -->
                <div id="aiStep3" class="d-none">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle"></i> AI가 생성한 소견입니다. 내용을 확인하고 필요시 수정한 후 저장하세요.
                    </div>

                    <form id="aiSaveForm" method="POST" action="index.php?route=consultation/store">
                        <input type="hidden" name="_csrf" id="aiCsrfToken" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                        <input type="hidden" name="service_type" id="aiSaveServiceType" value="POSTURE">
                        <input type="hidden" name="current_posture_session_id" id="aiSaveCurPosture" value="">
                        <input type="hidden" name="current_foot_session_id" id="aiSaveCurFoot" value="">
                        <input type="hidden" name="previous_posture_session_id" id="aiSavePrevPosture" value="">
                        <input type="hidden" name="previous_foot_session_id" id="aiSavePrevFoot" value="">
                        <input type="hidden" name="usage_log_id" id="aiUsageLogId" value="">

                        <div class="mb-3">
                            <label class="form-label fw-bold">종합 소견 <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="overall_assessment" id="aiOverallAssessment" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">개선된 점</label>
                            <textarea class="form-control" name="improvement_note" id="aiImprovementNote" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">우려/주의 사항</label>
                            <textarea class="form-control" name="concern_note" id="aiConcernNote" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">향후 권장 사항</label>
                            <textarea class="form-control" name="recommendation" id="aiRecommendation" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">전/후 비교 요약</label>
                            <textarea class="form-control" name="comparison_summary" id="aiComparisonSummary" rows="3"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-grow-1">
                                <i class="bi bi-check-lg"></i> 저장
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="aiRegenerateBtn">
                                <i class="bi bi-arrow-clockwise"></i> 다시 생성
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- 리포트 미리보기 모달 -->
<div class="modal fade" id="reportPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="reportPreviewTitle">
                    <i class="bi bi-eye"></i> 리포트 미리보기
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reportPreviewBody">
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="text-muted ms-2">리포트를 불러오는 중...</span>
                </div>
            </div>
        </div>
    </div>
</div>
