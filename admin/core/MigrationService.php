<?php
/**
 * 레거시 DB → TotalApp 마이그레이션 서비스
 *
 * 흐름:
 * 1. 업로드된 SQL 덤프를 임시 DB에 임포트
 * 2. 레거시 테이블 → TotalApp 테이블로 데이터 매핑/변환
 * 3. 이관 결과 검증 (건수 비교)
 * 4. 임시 DB 삭제
 *
 * 레거시 테이블 매핑:
 *   b_classcode          → class_code
 *   members              → member
 *   members_footprint    → (member의 height/weight 보완)
 *   members_ai_report    → posture_session + posture_keypoint + posture_report
 *   record_footprint     → foot_session + foot_report
 */
class MigrationService
{
    private $centralDb;
    private $tenantPdo;
    private $legacyPdo;
    private $tenantDbName;
    private $legacyDbName;
    private $log = [];
    private $counts = [];

    /**
     * 마이그레이션 실행
     *
     * @param int    $tenantId    가맹점 PK
     * @param array  $database    tenant_database 레코드
     * @param string $sqlFilePath 업로드된 SQL 파일 경로
     * @param int    $adminId     실행 관리자 PK
     * @return array ['success', 'message', 'log', 'counts']
     */
    public function migrate($tenantId, array $database, $sqlFilePath, $adminId)
    {
        $this->centralDb = Database::getInstance();
        $this->tenantDbName = $database['db_name'];
        $this->legacyDbName = $this->tenantDbName . '_legacy_tmp';

        // 프로비저닝 로그 기록
        $logId = $this->createLog($tenantId, $adminId);

        try {
            // 1. 임시 DB 생성 + SQL 임포트
            $this->addLog('임시 DB 생성: ' . $this->legacyDbName);
            $this->createLegacyDb($sqlFilePath);

            // 2. 레거시 테이블 존재 여부 확인
            $this->addLog('레거시 테이블 검증 중...');
            $this->validateLegacyTables();

            // 3. 가맹점 DB 연결
            $tenantDsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $database['db_host'], $database['db_port'], $this->tenantDbName);
            $this->tenantPdo = new PDO($tenantDsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // 4. FK 체크 해제 후 기존 데이터 정리 (프로비저닝 직후 초기 데이터만 있는 상태)
            $this->tenantPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            // 5. 단계별 이관
            $this->migrateClassCode();
            $this->migrateMembers();
            $this->migratePostureSessions();
            $this->migratePostureKeypoints();
            $this->migratePostureReports();
            $this->migrateFootSessions();
            $this->migrateFootReports();

            // 6. FK 체크 복원
            $this->tenantPdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            // 7. 검증
            $this->addLog('이관 검증 중...');
            $this->verify();

            // 8. 임시 DB 삭제
            $this->dropLegacyDb();
            $this->addLog('임시 DB 삭제 완료');

            // 9. 로그 완료
            $this->updateLog($logId, 'COMPLETED');

            return [
                'success' => true,
                'message' => '데이터 마이그레이션이 완료되었습니다.',
                'log'     => $this->log,
                'counts'  => $this->counts,
            ];

        } catch (Exception $e) {
            // 실패 시 임시 DB 정리 시도
            try { $this->dropLegacyDb(); } catch (Exception $ignore) {}

            $this->updateLog($logId, 'FAILED', $e->getMessage());
            $this->addLog('오류: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => '마이그레이션 실패: ' . $e->getMessage(),
                'log'     => $this->log,
                'counts'  => $this->counts,
            ];
        }
    }

    /**
     * 임시 DB 생성 및 SQL 임포트 (mysql CLI 사용)
     */
    private function createLegacyDb($sqlFilePath)
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4',
            PROVISION_DB_HOST, PROVISION_DB_PORT);
        $rootPdo = new PDO($dsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // 이미 존재하면 삭제
        $rootPdo->exec("DROP DATABASE IF EXISTS `{$this->legacyDbName}`");
        $rootPdo->exec("CREATE DATABASE `{$this->legacyDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // SQL 파일 전처리 (인코딩/엔진 변환)
        $sql = file_get_contents($sqlFilePath);
        if ($sql === false) {
            throw new Exception('SQL 파일을 읽을 수 없습니다.');
        }

        // euckr → utf8mb4 변환
        if (strpos($sql, 'euckr') !== false || strpos($sql, 'CHARSET=euckr') !== false) {
            $this->addLog('euckr 인코딩 감지 → utf8mb4 변환 적용');
            $sql = str_replace(
                ['CHARSET=euckr', 'charset=euckr', 'COLLATE=euckr_korean_ci', 'collate=euckr_korean_ci',
                 'character_set_client = euckr', 'SET NAMES euckr'],
                ['CHARSET=utf8mb4', 'charset=utf8mb4', 'COLLATE=utf8mb4_unicode_ci', 'collate=utf8mb4_unicode_ci',
                 'character_set_client = utf8mb4', 'SET NAMES utf8mb4'],
                $sql
            );
        }

        // MyISAM → InnoDB 변환
        $sql = str_replace('ENGINE=MyISAM', 'ENGINE=InnoDB', $sql);

        // 전처리된 SQL을 임시 파일로 저장
        $tmpSql = sys_get_temp_dir() . '/migration_' . $this->legacyDbName . '.sql';
        file_put_contents($tmpSql, $sql);
        unset($sql); // 메모리 해제

        // mysql CLI로 임포트 (대용량 파일 안전 처리)
        $mysqlBin = $this->findMysqlBin();
        $cmd = sprintf(
            '"%s" --host=%s --port=%d --user=%s %s --default-character-set=utf8mb4 %s < "%s" 2>&1',
            $mysqlBin,
            escapeshellarg(PROVISION_DB_HOST),
            PROVISION_DB_PORT,
            escapeshellarg(PROVISION_DB_USER),
            PROVISION_DB_PASS ? '--password=' . escapeshellarg(PROVISION_DB_PASS) : '',
            escapeshellarg($this->legacyDbName),
            $tmpSql
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        // 임시 파일 삭제
        @unlink($tmpSql);

        if ($returnCode !== 0) {
            $errorMsg = implode("\n", $output);
            throw new Exception("SQL 임포트 실패 (exit code {$returnCode}): {$errorMsg}");
        }

        // 레거시 DB PDO 연결 (이후 검증/조회용)
        $legacyDsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            PROVISION_DB_HOST, PROVISION_DB_PORT, $this->legacyDbName);
        $this->legacyPdo = new PDO($legacyDsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $this->addLog('SQL 임포트 완료 (mysql CLI)');
    }

    /**
     * mysql 실행 파일 경로 탐색
     */
    private function findMysqlBin()
    {
        // 설정된 경로가 있으면 우선 사용
        if (defined('MYSQL_BIN_PATH') && file_exists(MYSQL_BIN_PATH)) {
            return MYSQL_BIN_PATH;
        }

        // 일반적인 경로 탐색
        $candidates = [
            'D:/xampp/mysql/bin/mysql.exe',
            'C:/xampp/mysql/bin/mysql.exe',
            'C:/APM_Setup/mysql/bin/mysql.exe',
            'C:/Program Files/MariaDB 10.5/bin/mysql.exe',
            'C:/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // PATH에서 찾기
        $which = trim(shell_exec('where mysql 2>NUL') ?: '');
        if ($which && file_exists($which)) {
            return $which;
        }

        throw new Exception('mysql 실행 파일을 찾을 수 없습니다. config.php에 MYSQL_BIN_PATH를 설정해주세요.');
    }

    /**
     * 필수 레거시 테이블 존재 여부 확인
     */
    private function validateLegacyTables()
    {
        $required = ['members'];
        $optional = ['b_classcode', 'members_footprint', 'members_ai_report', 'record_footprint'];

        foreach ($required as $table) {
            $exists = $this->legacyTableExists($table);
            if (!$exists) {
                throw new Exception("필수 테이블 '{$table}'이(가) SQL 덤프에 없습니다.");
            }
            $this->addLog("필수 테이블 확인: {$table} ✓");
        }

        foreach ($optional as $table) {
            $exists = $this->legacyTableExists($table);
            $this->addLog(sprintf("선택 테이블: %s %s", $table, $exists ? '✓' : '(없음 - 건너뜀)'));
        }
    }

    private function legacyTableExists($table)
    {
        $stmt = $this->legacyPdo->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?"
        );
        $stmt->execute([$this->legacyDbName, $table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * 수강반 이관: b_classcode → class_code
     */
    private function migrateClassCode()
    {
        if (!$this->legacyTableExists('b_classcode')) {
            $this->addLog('[수강반] b_classcode 테이블 없음 → 건너뜀');
            $this->counts['class_code'] = 0;
            return;
        }

        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;

        // 기존 초기 데이터(TotalApp.sql의 시드) 외 추가분만 이관
        $sql = "INSERT INTO `{$tenantDb}`.class_code (franchise_id, code, name)
                SELECT 1, l.code, l.name
                FROM `{$legacyDb}`.b_classcode l
                WHERE NOT EXISTS (
                    SELECT 1 FROM `{$tenantDb}`.class_code t
                    WHERE t.code = l.code
                )";
        $count = $this->tenantPdo->exec($sql);
        $this->counts['class_code'] = $count;
        $this->addLog("[수강반] {$count}건 이관 완료");
    }

    /**
     * 회원 이관: members + members_footprint → member
     */
    private function migrateMembers()
    {
        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;

        // members_footprint에서 최신 키/몸무게 가져오기 (서브쿼리)
        $heightWeightJoin = '';
        if ($this->legacyTableExists('members_footprint')) {
            $heightWeightJoin = "LEFT JOIN (
                SELECT members_num, uheight, uweight,
                       ROW_NUMBER() OVER (PARTITION BY members_num ORDER BY signdate DESC) rn
                FROM `{$legacyDb}`.members_footprint
            ) mf ON mf.members_num = m.num AND mf.rn = 1";
        }

        $heightCol = $heightWeightJoin ? "NULLIF(mf.uheight, '0')" : 'NULL';
        $weightCol = $heightWeightJoin ? "NULLIF(mf.uweight, '0')" : 'NULL';

        $sql = "INSERT INTO `{$tenantDb}`.member
                    (id, franchise_id, name, phone, gender, birth_date, height, weight, memo, consultation_enabled, status, created_at)
                SELECT
                    m.num,
                    1,
                    m.uname,
                    COALESCE(NULLIF(m.sphone, ''), NULLIF(m.mphone, ''), ''),
                    CASE m.sex WHEN '남' THEN 'M' WHEN '여' THEN 'F' ELSE NULL END,
                    CASE
                        WHEN m.uyear != '' AND m.umonth != '' AND m.uday != ''
                            AND m.uyear REGEXP '^[0-9]+$' AND m.umonth REGEXP '^[0-9]+$' AND m.uday REGEXP '^[0-9]+$'
                        THEN STR_TO_DATE(CONCAT(m.uyear, '-', LPAD(m.umonth,2,'0'), '-', LPAD(m.uday,2,'0')), '%Y-%m-%d')
                        ELSE NULL
                    END,
                    {$heightCol},
                    {$weightCol},
                    NULLIF(m.comment, ''),
                    0,
                    CASE m.bigo
                        WHEN '입관' THEN 'ACTIVE'
                        WHEN '신규' THEN 'ACTIVE'
                        WHEN '일반' THEN 'ACTIVE'
                        WHEN '일반신규' THEN 'ACTIVE'
                        WHEN '복관' THEN 'ACTIVE'
                        WHEN '휴관' THEN 'PAUSED'
                        WHEN '명예' THEN 'HONORARY'
                        WHEN '퇴관' THEN 'WITHDRAWN'
                        ELSE 'ACTIVE'
                    END,
                    CASE
                        WHEN m.signdate > 0 THEN FROM_UNIXTIME(m.signdate)
                        ELSE NOW()
                    END
                FROM `{$legacyDb}`.members m
                {$heightWeightJoin}";

        $count = $this->tenantPdo->exec($sql);
        $this->counts['member'] = $count;
        $this->addLog("[회원] {$count}건 이관 완료");
    }

    /**
     * AI자세분석 세션 이관: members_ai_report → posture_session
     */
    private function migratePostureSessions()
    {
        if (!$this->legacyTableExists('members_ai_report')) {
            $this->addLog('[자세분석 세션] members_ai_report 테이블 없음 → 건너뜀');
            $this->counts['posture_session'] = 0;
            return;
        }

        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;

        $sql = "INSERT INTO `{$tenantDb}`.posture_session
                    (id, member_id, height, weight, img_x_size, img_y_size, status, captured_at, created_at)
                SELECT
                    r.num,
                    r.num_members,
                    NULLIF(r.dre_height, 0),
                    NULLIF(r.dre_weight, 0),
                    NULLIF(r.img_x_size, 0),
                    NULLIF(r.img_y_size, 0),
                    'COMPLETED',
                    CASE WHEN r.report_date = '0000-00-00 00:00:00' THEN NOW() ELSE r.report_date END,
                    CASE WHEN r.report_date = '0000-00-00 00:00:00' THEN NOW() ELSE r.report_date END
                FROM `{$legacyDb}`.members_ai_report r
                WHERE r.num_members IN (SELECT id FROM `{$tenantDb}`.member)";

        $count = $this->tenantPdo->exec($sql);
        $this->counts['posture_session'] = $count;
        $this->addLog("[자세분석 세션] {$count}건 이관 완료");
    }

    /**
     * AI자세분석 키포인트 이관: members_ai_report → posture_keypoint (정면/우측면/좌측면)
     */
    private function migratePostureKeypoints()
    {
        if (!$this->legacyTableExists('members_ai_report')) {
            $this->counts['posture_keypoint'] = 0;
            return;
        }

        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;
        $total = 0;

        // 정면 키포인트
        $sql = "INSERT INTO `{$tenantDb}`.posture_keypoint
                    (session_id, view_type, kp_nose, kp_left_eye, kp_right_eye, kp_left_ear, kp_right_ear,
                     kp_left_shoulder, kp_right_shoulder, kp_left_elbow, kp_right_elbow,
                     kp_left_wrist, kp_right_wrist, kp_left_hip, kp_right_hip,
                     kp_left_knee, kp_right_knee, kp_left_ankle, kp_right_ankle)
                SELECT
                    r.num, 'FRONT',
                    r.kp_front_nose, r.kp_front_left_eye, r.kp_front_right_eye,
                    r.kp_front_left_ear, r.kp_front_right_ear,
                    r.kp_front_left_shoulder, r.kp_front_right_shoulder,
                    r.kp_front_left_arm, r.kp_front_right_arm,
                    r.kp_front_left_hand, r.kp_front_right_hand,
                    r.kp_front_left_hip, r.kp_front_right_hip,
                    r.kp_front_left_knee, r.kp_front_right_knee,
                    r.kp_front_left_foot, r.kp_front_right_foot
                FROM `{$legacyDb}`.members_ai_report r
                WHERE r.num IN (SELECT id FROM `{$tenantDb}`.posture_session)";
        $total += $this->tenantPdo->exec($sql);

        // 우측면 키포인트
        $sql = "INSERT INTO `{$tenantDb}`.posture_keypoint
                    (session_id, view_type, kp_nose, kp_left_eye, kp_left_ear,
                     kp_left_shoulder, kp_left_elbow, kp_left_wrist,
                     kp_left_hip, kp_left_knee, kp_left_ankle)
                SELECT
                    r.num, 'SIDE_RIGHT',
                    r.kp_side_nose, r.kp_side_eye, r.kp_side_ear,
                    r.kp_side_shoulder, r.kp_side_arm, r.kp_side_hand,
                    r.kp_side_hip, r.kp_side_knee, r.kp_side_foot
                FROM `{$legacyDb}`.members_ai_report r
                WHERE r.num IN (SELECT id FROM `{$tenantDb}`.posture_session)";
        $total += $this->tenantPdo->exec($sql);

        // 좌측면 키포인트
        $sql = "INSERT INTO `{$tenantDb}`.posture_keypoint
                    (session_id, view_type, kp_nose, kp_left_eye, kp_left_ear,
                     kp_left_shoulder, kp_left_elbow, kp_left_wrist,
                     kp_left_hip, kp_left_knee, kp_left_ankle)
                SELECT
                    r.num, 'SIDE_LEFT',
                    r.kp_other_side_nose, r.kp_other_side_eye, r.kp_other_side_ear,
                    r.kp_other_side_shoulder, r.kp_other_side_arm, r.kp_other_side_hand,
                    r.kp_other_side_hip, r.kp_other_side_knee, r.kp_other_side_foot
                FROM `{$legacyDb}`.members_ai_report r
                WHERE r.num IN (SELECT id FROM `{$tenantDb}`.posture_session)";
        $total += $this->tenantPdo->exec($sql);

        $this->counts['posture_keypoint'] = $total;
        $this->addLog("[자세분석 키포인트] {$total}건 이관 완료 (정면+우측면+좌측면)");
    }

    /**
     * AI자세분석 리포트 이관: members_ai_report → posture_report
     */
    private function migratePostureReports()
    {
        if (!$this->legacyTableExists('members_ai_report')) {
            $this->counts['posture_report'] = 0;
            return;
        }

        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;

        $sql = "INSERT INTO `{$tenantDb}`.posture_report (session_id,
                  horizontal_eye_angle, horizontal_shoulder_angle, horizontal_hip_angle, horizontal_leg_angle,
                  horizontal_eye_direction, horizontal_shoulder_direction, horizontal_hip_direction, horizontal_leg_direction,
                  shoulder_ear_angle, shoulder_ear_direction, foot_shoulder_angle, foot_shoulder_direction,
                  shoulder_hip_angle, shoulder_hip_direction, foot_leg_angle, foot_leg_direction,
                  other_shoulder_ear_angle, other_shoulder_ear_direction, other_foot_shoulder_angle, other_foot_shoulder_direction,
                  other_shoulder_hip_angle, other_shoulder_hip_direction, other_foot_leg_angle, other_foot_leg_direction,
                  spine_angle, pcmt, height_loss, balance_point_x, balance_point_y, total_deviation, posture_score,
                  genu_varus_type, left_genu_varus_angle, left_genu_varus_direction, right_genu_varus_angle, right_genu_varus_direction,
                  left_back_knee, right_back_knee,
                  front_user_img, side_right_user_img, side_left_user_img,
                  front_process_img, side_right_process_img, side_left_process_img,
                  skeleton_current_front_img, skeleton_current_side_img,
                  skeleton_future_front_img, skeleton_future_side_img,
                  fhp_img, left_genu_varus_img, right_genu_varus_img,
                  left_back_knee_img, right_back_knee_img,
                  created_at)
                SELECT
                  r.num,
                  r.horizontal_eye_angle, r.horizontal_shoulder_angle, r.horizontal_hip_angle, r.horizontal_leg_angle,
                  r.horizontal_eye_direction, r.horizontal_shoulder_direction, r.horizontal_hip_direction, r.horizontal_leg_direction,
                  r.shoulder_ear_angle, r.shoulder_ear_direction, r.foot_shoulder_angle, r.foot_shoulder_direction,
                  r.shoulder_hip_angle, r.shoulder_hip_direction, r.foot_leg_angle, r.foot_leg_direction,
                  r.other_shoulder_ear_angle, r.other_shoulder_ear_direction, r.other_foot_shoulder_angle, r.other_foot_shoulder_direction,
                  r.other_shoulder_hip_angle, r.other_shoulder_hip_direction, r.other_foot_leg_angle, r.other_foot_leg_direction,
                  r.spine_angle, r.pcmt, r.lossing_height, r.balance_point_x, r.balance_point_y, r.total_devlation, r.posture_number,
                  r.genu_varus, r.left_genu_varus_angle, r.left_genu_varus_direction, r.right_genu_varus_angle, r.right_genu_varus_direction,
                  r.left_back_knee, r.right_back_knee,
                  NULLIF(r.front_user_img, ''), NULLIF(r.side_user_img, ''), NULLIF(r.other_side_user_img, ''),
                  NULLIF(r.front_process_img, ''), NULLIF(r.side_process_img, ''), NULLIF(r.other_side_process_img, ''),
                  NULLIF(r.back_current_front_img, ''), NULLIF(r.back_current_side_img, ''),
                  NULLIF(r.back_future_front_img, ''), NULLIF(r.back_future_side_img, ''),
                  NULLIF(r.fhp_img, ''), NULLIF(r.left_genu_varus_img, ''), NULLIF(r.right_genu_varus_img, ''),
                  NULLIF(r.left_back_knee_img, ''), NULLIF(r.right_back_knee_img, ''),
                  CASE WHEN r.report_date = '0000-00-00 00:00:00' THEN NOW() ELSE r.report_date END
                FROM `{$legacyDb}`.members_ai_report r
                WHERE r.num IN (SELECT id FROM `{$tenantDb}`.posture_session)";

        $count = $this->tenantPdo->exec($sql);
        $this->counts['posture_report'] = $count;
        $this->addLog("[자세분석 리포트] {$count}건 이관 완료");
    }

    /**
     * AIoT족부분석 세션 이관: record_footprint → foot_session
     */
    private function migrateFootSessions()
    {
        if (!$this->legacyTableExists('record_footprint')) {
            $this->addLog('[족부분석 세션] record_footprint 테이블 없음 → 건너뜀');
            $this->counts['foot_session'] = 0;
            return;
        }

        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;

        $sql = "INSERT INTO `{$tenantDb}`.foot_session
                    (id, member_id, height, weight, bmi, age, status,
                     img_url_1, img_url_2, img_url_3, img_url_4,
                     select_img_url, cmpx, captured_at, created_at)
                SELECT
                    rf.num,
                    rf.members_num,
                    NULLIF(rf.now_height, ''),
                    NULLIF(rf.now_weight, ''),
                    NULL,
                    NULL,
                    CASE COALESCE(rf.ready, 'OK')
                        WHEN 'WAIT' THEN 'WAIT'
                        WHEN 'ERROR' THEN 'ERROR'
                        ELSE 'OK'
                    END,
                    NULLIF(rf.img_url_1, ''),
                    NULLIF(rf.img_url_2, ''),
                    NULLIF(rf.img_url_3, ''),
                    NULLIF(rf.img_url_4, ''),
                    NULLIF(rf.select_img_url, ''),
                    NULLIF(rf.cmpx, ''),
                    rf.regdate,
                    rf.regdate
                FROM `{$legacyDb}`.record_footprint rf
                WHERE rf.members_num IN (SELECT id FROM `{$tenantDb}`.member)";

        $count = $this->tenantPdo->exec($sql);
        $this->counts['foot_session'] = $count;
        $this->addLog("[족부분석 세션] {$count}건 이관 완료");
    }

    /**
     * AIoT족부분석 리포트 이관: record_footprint → foot_report
     */
    private function migrateFootReports()
    {
        if (!$this->legacyTableExists('record_footprint')) {
            $this->counts['foot_report'] = 0;
            return;
        }

        $legacyDb = $this->legacyDbName;
        $tenantDb = $this->tenantDbName;

        $sql = "INSERT INTO `{$tenantDb}`.foot_report (session_id,
                  left_foot_length, left_foot_width, right_foot_length, right_foot_width,
                  left_forefoot, left_arch, left_heel, right_forefoot, right_arch, right_heel,
                  left_top_distance, left_middle_distance, left_bottom_distance,
                  right_top_distance, right_middle_distance, right_bottom_distance,
                  left_staheli, right_staheli, left_chippaux, right_chippaux,
                  left_clarke, right_clarke, left_arch_index, right_arch_index,
                  left_foot_type, right_foot_type,
                  hallux_valgus_left_angle, hallux_valgus_right_angle,
                  hallux_valgus_left_img, hallux_valgus_right_img,
                  pelvis, spine,
                  footprint_img, heatmap_img, left_footprint_img, right_footprint_img, orthotic_img,
                  orthotic_left_length, orthotic_right_length, orthotic_left_width, orthotic_right_width, orthotic_points,
                  memo, created_at)
                SELECT
                  rf.num,
                  rf.left_foot_length, rf.left_foot_width, rf.right_foot_length, rf.right_foot_width,
                  rf.left_forefoot, rf.left_arch, rf.left_heel, rf.right_forefoot, rf.right_arch, rf.right_heel,
                  NULLIF(rf.left_top_distance, ''), NULLIF(rf.left_middle_distance, ''), NULLIF(rf.left_bottom_distance, ''),
                  NULLIF(rf.right_top_distance, ''), NULLIF(rf.right_middle_distance, ''), NULLIF(rf.right_bottom_distance, ''),
                  NULLIF(rf.left_staheli, ''), NULLIF(rf.right_staheli, ''),
                  NULLIF(rf.left_chippaux, ''), NULLIF(rf.right_chippaux, ''),
                  NULLIF(rf.left_clarke, ''), NULLIF(rf.right_clarke, ''),
                  NULLIF(rf.left_ai, ''), NULLIF(rf.right_ai, ''),
                  NULLIF(rf.left_footprint_result, ''), NULLIF(rf.right_footprint_result, ''),
                  NULLIF(rf.moozi_left, ''), NULLIF(rf.moozi_right, ''),
                  NULLIF(rf.moozi_left_url, ''), NULLIF(rf.moozi_right_url, ''),
                  NULLIF(rf.pelvis, ''), NULLIF(rf.spine, ''),
                  NULLIF(rf.footprint_url, ''), NULLIF(rf.heatmap_url, ''),
                  NULLIF(rf.left_footprint_url, ''), NULLIF(rf.right_footprint_url, ''),
                  NULLIF(rf.osotic_image_url, ''),
                  NULLIF(rf.osotic_height_left, ''), NULLIF(rf.osotic_height_right, ''),
                  NULLIF(rf.osotic_width_left, ''), NULLIF(rf.osotic_width_right, ''),
                  NULLIF(rf.osotic_points, ''),
                  rf.memo,
                  rf.regdate
                FROM `{$legacyDb}`.record_footprint rf
                WHERE rf.num IN (SELECT id FROM `{$tenantDb}`.foot_session)
                  AND COALESCE(rf.ready, 'OK') != 'WAIT'";

        $count = $this->tenantPdo->exec($sql);
        $this->counts['foot_report'] = $count;
        $this->addLog("[족부분석 리포트] {$count}건 이관 완료");
    }

    /**
     * 이관 결과 검증 (건수 비교)
     */
    private function verify()
    {
        $tenantDb = $this->tenantDbName;
        $legacyDb = $this->legacyDbName;

        $checks = [
            ['회원', 'member', 'members', "{$tenantDb}", "{$legacyDb}"],
        ];

        if ($this->legacyTableExists('members_ai_report')) {
            $checks[] = ['자세분석 리포트', 'posture_report', 'members_ai_report', $tenantDb, $legacyDb];
        }
        if ($this->legacyTableExists('record_footprint')) {
            $checks[] = ['족부분석 세션', 'foot_session', 'record_footprint', $tenantDb, $legacyDb];
        }

        foreach ($checks as $c) {
            $stmt = $this->tenantPdo->query("SELECT COUNT(*) FROM `{$c[3]}`.`{$c[1]}`");
            $newCount = (int)$stmt->fetchColumn();

            $stmt = $this->legacyPdo->query("SELECT COUNT(*) FROM `{$c[4]}`.`{$c[2]}`");
            $legacyCount = (int)$stmt->fetchColumn();

            $match = ($newCount >= $legacyCount) ? '✓' : '⚠ 차이 있음';
            $this->addLog("[검증] {$c[0]}: 레거시 {$legacyCount}건 → 신규 {$newCount}건 {$match}");
        }
    }

    /**
     * 임시 DB 삭제
     */
    private function dropLegacyDb()
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4',
            PROVISION_DB_HOST, PROVISION_DB_PORT);
        $rootPdo = new PDO($dsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $rootPdo->exec("DROP DATABASE IF EXISTS `{$this->legacyDbName}`");
    }

    private function addLog($message)
    {
        $this->log[] = date('H:i:s') . ' ' . $message;
    }

    private function createLog($tenantId, $adminId)
    {
        $stmt = $this->centralDb->prepare(
            "INSERT INTO provision_log (tenant_id, admin_id, action, status, to_version, detail, started_at, created_at)
             VALUES (?, ?, 'MIGRATE', 'IN_PROGRESS', ?, '레거시 데이터 마이그레이션', NOW(), NOW())"
        );
        $stmt->execute([$tenantId, $adminId, APP_VERSION]);
        return (int)$this->centralDb->lastInsertId();
    }

    private function updateLog($logId, $status, $errorMessage = null)
    {
        $stmt = $this->centralDb->prepare(
            "UPDATE provision_log SET status = ?, error_message = ?, completed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $errorMessage, $logId]);
    }
}
